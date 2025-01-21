<?php
/*************************************************************************
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Batch\FeedSource;

use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\Batch\BatchIteratorInterface;
use Magento\DataExporter\Model\Batch\BatchTableFactory;
use Magento\DataExporter\Model\Batch\BatchLocatorFactory;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Model\Logging\LogRegistry;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Creates batches based on feed source table and configured batch size.
 * Used in the following scenarios:
 * - full reindex. Can be triggered by `indexer_reindex_all_invalid` cron job or by indexer:reindex CLI command
 * - full sync. Can be triggered by saas:resync CLI command
 */
class Generator implements BatchGeneratorInterface
{
    private const MAX_PROCESSED_ITEMS_PER_ITERATION = 10000000;

    /**
     * List of filterable source field types
     */
    private const FILTERABLE_FIELD_TYPES = [
        'int',
        'tinyint',
        'smallint',
        'mediumint',
        'bigint'
    ];

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var IteratorFactory
     */
    private IteratorFactory $iteratorFactory;

    /**
     * @var BatchLocatorFactory
     */
    private BatchLocatorFactory $batchLocatorFactory;

    /**
     * @var BatchTableFactory
     */
    private BatchTableFactory $batchTableFactory;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param IteratorFactory $iteratorFactory
     * @param BatchLocatorFactory $batchLocatorFactory
     * @param BatchTableFactory $batchTableFactory
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection  $resourceConnection,
        IteratorFactory     $iteratorFactory,
        BatchLocatorFactory $batchLocatorFactory,
        BatchTableFactory   $batchTableFactory,
        CommerceDataExportLoggerInterface $logger,
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->iteratorFactory = $iteratorFactory;
        $this->batchLocatorFactory = $batchLocatorFactory;
        $this->batchTableFactory = $batchTableFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function generate(FeedIndexMetadata $metadata, array $args = []): BatchIteratorInterface
    {
        try {
            $batchIterator = $this->doGenerate($metadata, $args);
            $this->logger->addContext([LogRegistry::TOTAL_ITERATIONS => $batchIterator->count()]);
            return $batchIterator;
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    '%s feed: error occurred: %s',
                    $metadata->getFeedName(),
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
            throw $e;
        }
    }

    /**
     * Generate batch iterator
     *
     * @param FeedIndexMetadata $metadata
     * @param array $args
     * @return BatchIteratorInterface
     * @throws \Zend_Db_Exception
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function doGenerate(FeedIndexMetadata $metadata, array $args = []): BatchIteratorInterface
    {
        $connection = $this->resourceConnection->getConnection();
        // feed exporter must use a master connection because reading from slaves occurs with a delay,
        // which leads to data loss
        if ($connection instanceof \Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy) {
            $connection->setUseMasterConnection();
        }
        $sourceTableName = $this->resourceConnection->getTableName($metadata->getSourceTableName());
        $sourceTableField = $metadata->getSourceTableField();

        $sequenceSourceTableName = $this->getSequenceSourceTable($sourceTableName, $sourceTableField);
        if ($sequenceSourceTableName) {
            $sourceTableName = $sequenceSourceTableName;
            $sourceTableField = 'sequence_value';
        }

        $sequenceTableName = $this->resourceConnection->getTableName(
            sprintf("%s_index_sequence", $metadata->getFeedTableName())
        );
        $batchLocator = $this->batchLocatorFactory->create(['sequenceTableName' => $sequenceTableName]);
        $batchLocator->init();

        $batchTableName = $this->resourceConnection->getTableName(
            sprintf("%s_index_batches", $metadata->getFeedTableName())
        );
        $batchTable = $this->batchTableFactory->create(
            [
                'batchTableName' => $batchTableName,
                'sourceTableName' => $sourceTableName,
                'sourceTableKeyColumns' => [$sourceTableField]
            ]
        );
        $startFrom = null;
        if ($metadata->isResyncShouldBeContinued() && $this->isSourceEntityFieldFilterable($metadata)) {
            $startFrom = $this->getLastExportedId($metadata);
            // to cover case when feed table is empty but source table doesn't start from 1
            if ($startFrom <= 0) {
                $startFrom = $this->getStartFromValue($sourceTableName, $sourceTableField);
            }
        } elseif ($this->isSourceEntityFieldFilterable($metadata)) {
            $startFrom = $this->getStartFromValue($sourceTableName, $sourceTableField);
        }

        $maxProcessedItemsLimit = $startFrom + self::MAX_PROCESSED_ITEMS_PER_ITERATION;
        $batchNumberIncrement = 0;
        $processedItems = 0;
        $totalProcessedItems = 0;

        while (true) {
        $insertDataQuery = $connection->insertFromSelect(
                $this->getSelect(
                    $metadata,
                    $sourceTableName,
                    $sourceTableField,
                    $batchTable->getBatchNumberField(),
                    $batchNumberIncrement,
                    $startFrom,
                    $maxProcessedItemsLimit),
            $batchTable->getBatchTableName(),
            [$batchTable->getBatchNumberField(), $sourceTableField]
        );
            // TOTO: make initialization more explicit
            $initializeCreate = $processedItems === 0;
            if ($initializeCreate) {
                $this->logger->info(sprintf(
                        'Creating batch table `%s`. Start position: %s',
                        $batchTable->getBatchTableName(),
                        $startFrom
                    )
                );
            }
            $processedItems = $batchTable->create($insertDataQuery, $initializeCreate);
            if (!$initializeCreate && $processedItems > 0) {
                $this->logger->info(
                    sprintf(
                        'Continue fulfilling batch table `%s`. Start position: %s, end: %s',
                        $batchTable->getBatchTableName(),
                        $startFrom,
                        $maxProcessedItemsLimit
                    )
                );
            }

            $totalProcessedItems+= $processedItems;
            $batchNumberIncrement = intdiv($totalProcessedItems, $metadata->getBatchSize());

            if ($processedItems === 0) {
                break;
            }
            $startFrom += self::MAX_PROCESSED_ITEMS_PER_ITERATION;
            $maxProcessedItemsLimit += self::MAX_PROCESSED_ITEMS_PER_ITERATION;
        }

        $this->logger->info(
            sprintf(
                'Batch table `%s` created. Total Items: %s, batches: ~%s',
                $batchTable->getBatchTableName(),
                $totalProcessedItems,
                $batchNumberIncrement
            )
        );
        $this->logger->info(
            $totalProcessedItems > 0
                ? sprintf(
                'start processing `%s` items in `%s` threads with `%s` batch size',
                $totalProcessedItems,
                $metadata->getThreadCount(),
                $metadata->getBatchSize()
            )
                : sprintf(
                'nothing to process - no items to sync. Not expected? Are there any items in source table `%s`?',
                $sourceTableName
            )
        );

        return $this->iteratorFactory->create(
            [
                'batchTable' => $batchTable,
                'sourceTableKeyColumn' => $sourceTableField,
                'batchLocator' => $batchLocator,
            ]
        );
    }

    /**
     * Returns select for batch generation.
     *
     * @param FeedIndexMetadata $metadata
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param string $batchNumField
     * @param int $batchNumberIncrement
     * @param int|null $startFrom
     * @param int|null $maxLimit
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    private function getSelect(
        FeedIndexMetadata $metadata,
        string $sourceTableName,
        string $sourceTableField,
        string $batchNumField,
        int $batchNumberIncrement = 0,
        ?int $startFrom = null,
        ?int $maxLimit = null
    ): Select {
        $connection = $this->resourceConnection->getConnection();
        $tableFeed = $this->resourceConnection->getTableName($metadata->getFeedTableName());

        $feedSelect = $connection->select()
            ->from(
                ['f' => $tableFeed],
                [
                    $sourceTableField => "f.{$metadata->getFeedTableField()}"
                ]
            )
            ->joinLeft(
                [
                    's' => $sourceTableName
                ],
                "f.{$metadata->getFeedTableField()} = s.$sourceTableField",
                []
            )
            ->where("s.$sourceTableField IS NULL");
        $sourceSelect = $connection->select()
            ->from(
                ['s' => $sourceTableName],
                [
                    $sourceTableField => "s.$sourceTableField"
                ]
            );
        $unionSelect = $connection->select()->union([$feedSelect, $sourceSelect]);

        if ($metadata->getFullReIndexSecondsLimit() !== 0) {
            $dateTime = date_create();
            $sourceSelect->where(sprintf(
                "s.%s >= DATE_SUB(STR_TO_DATE('%s', '%%Y-%%m-%%d %%H:%%i:%%s'), INTERVAL %d SECOND)",
                $metadata->getSourceTableFieldOnFullReIndexLimit(),
                $dateTime->format($metadata->getDbDateTimeFormat()),
                $metadata->getFullReIndexSecondsLimit()
            ));
        }

        $select = $connection->select()
            ->from(
                ['t' => $unionSelect],
                [
                    $batchNumField => new \Zend_Db_Expr(
                        sprintf(
                            "CEILING(ROW_NUMBER() OVER (ORDER BY %s)/%d) + $batchNumberIncrement",
                            $sourceTableField,
                            $metadata->getBatchSize()
                        )
                    ),
                    $sourceTableField
                ]
            );
        if ($startFrom !== null && $maxLimit !== null) {
            $select->where(sprintf('%s > %s', $sourceTableField, $startFrom));
            $select->where(sprintf('%s <= %s', $sourceTableField, $maxLimit));
            }

        return $select;
    }

    /**
     * Get last exported id
     *
     * @param FeedIndexMetadata $metadata
     * @return int
     */
    private function getLastExportedId(FeedIndexMetadata $metadata): int
    {
        $select = $this->resourceConnection->getConnection()->select()->from(
            $this->resourceConnection->getTableName($metadata->getFeedTableName()),
            [$metadata->getFeedTableField() => sprintf('max(%s)', $metadata->getFeedTableField())]
        );
        return (int)$this->resourceConnection->getConnection()->fetchOne($select);
    }

    /**
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @return int
     */
    private function getStartFromValue(string $sourceTableName, string $sourceTableField): int
    {
        $select = $this->resourceConnection->getConnection()->select()->from(
            $sourceTableName,
            [$sourceTableField => sprintf('min(%s)', $sourceTableField)]
        );
        $startValue = (int)$this->resourceConnection->getConnection()->fetchOne($select);
        return $startValue > 0 ? --$startValue : $startValue;
    }

    /**
     * Returns sequence table related to source table if exists.
     *
     * Used instead of source table for performance reasons.
     *
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @return ?string
     */
    private function getSequenceSourceTable(string $sourceTableName, string $sourceTableField): ?string
    {
        $foreignKeys = $this->resourceConnection->getConnection()->getForeignKeys($sourceTableName);
        foreach ($foreignKeys as $key) {
            if ($key['COLUMN_NAME'] === $sourceTableField && $key['REF_COLUMN_NAME'] === 'sequence_value') {
                return $key['REF_TABLE_NAME'];
            }
        }

        return null;
    }

    /**
     * Check if source entity field is integer
     *
     * @param FeedIndexMetadata $metadata
     * @return bool
     */
    private function isSourceEntityFieldFilterable(FeedIndexMetadata $metadata): bool
    {
        $sourceTableDescribed = $this->resourceConnection->getConnection()
            ->describeTable($this->resourceConnection->getTableName($metadata->getSourceTableName()));
        if (isset($sourceTableDescribed[$metadata->getSourceTableField()])) {
            return \in_array(
                $sourceTableDescribed[$metadata->getSourceTableField()]['DATA_TYPE'],
                self::FILTERABLE_FIELD_TYPES,
                true
            );
        }
        return false;
    }
}
