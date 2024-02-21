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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Creates batches based on feed source table and configured batch size.
 */
class Generator implements BatchGeneratorInterface
{
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
     * @param ResourceConnection $resourceConnection
     * @param IteratorFactory $iteratorFactory
     * @param BatchLocatorFactory $batchLocatorFactory
     * @param BatchTableFactory $batchTableFactory
     */
    public function __construct(
        ResourceConnection  $resourceConnection,
        IteratorFactory     $iteratorFactory,
        BatchLocatorFactory $batchLocatorFactory,
        BatchTableFactory   $batchTableFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->iteratorFactory = $iteratorFactory;
        $this->batchLocatorFactory = $batchLocatorFactory;
        $this->batchTableFactory = $batchTableFactory;
    }

    /**
     * @inheritDoc
     */
    public function generate(FeedIndexMetadata $metadata, array $args = []): BatchIteratorInterface
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
        $insertDataQuery = $connection->insertFromSelect(
            $this->getSelect($metadata, $sourceTableName, $sourceTableField, $batchTable->getBatchNumberField()),
            $batchTable->getBatchTableName(),
            [$batchTable->getBatchNumberField(), $sourceTableField]
        );
        $batchTable->create($insertDataQuery);

        $batchIterator = $this->iteratorFactory->create(
            [
                'batchTable' => $batchTable,
                'sourceTableKeyColumn' => $sourceTableField,
                'batchLocator' => $batchLocator,
            ]
        );

        return $batchIterator;
    }

    /**
     * Returns select for batch generation.
     *
     * @param FeedIndexMetadata $metadata
     * @param string $sourceTableName
     * @param string $sourceTableField
     * @param string $batchNumField
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    private function getSelect(
        FeedIndexMetadata $metadata,
        string $sourceTableName,
        string $sourceTableField,
        string $batchNumField
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
                $dateTime->format('Y-m-d H:i:s'),
                $metadata->getFullReIndexSecondsLimit()
            ));
        }

        $select = $connection->select()
            ->from(
                ['t' => $unionSelect],
                [
                    $batchNumField => new \Zend_Db_Expr(
                        sprintf(
                            "CEILING(ROW_NUMBER() OVER (ORDER BY %s)/%d)",
                            $sourceTableField,
                            $metadata->getBatchSize()
                        )
                    ),
                    $sourceTableField
                ]
            );
        if ($metadata->isResyncShouldBeContinued()) {
            $max = $this->getLastExportedId($metadata);
            if ($max && $max > 0) {
                $select->where(sprintf('%s > %s', $sourceTableField, $max));
            }
        }

        return $select;
    }

    /**
     * Get last exported id
     *
     * @param FeedIndexMetadata $metadata
     * @return string
     */
    private function getLastExportedId(FeedIndexMetadata $metadata)
    {
        $select = $this->resourceConnection->getConnection()->select()->from(
            $this->resourceConnection->getTableName($metadata->getFeedTableName()),
            [$metadata->getFeedTableField() => sprintf('max(%s)', $metadata->getFeedTableField())]
        );
        return $this->resourceConnection->getConnection()->fetchOne($select);
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
}
