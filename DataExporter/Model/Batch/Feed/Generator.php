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

namespace Magento\DataExporter\Model\Batch\Feed;

use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\Batch\BatchIteratorInterface;
use Magento\DataExporter\Model\Batch\BatchTableFactory;
use Magento\DataExporter\Model\Batch\BatchLocatorFactory;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\DataExporter\Model\Batch\FeedSource\IteratorFactory as IdIteratorFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Creates batches based on feed index table and configured batch size.
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
     * @var IdIteratorFactory
     */
    private IdIteratorFactory $idIteratorFactory;

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
     * @param IdIteratorFactory $idIteratorFactory
     * @param BatchLocatorFactory $batchLocatorFactory
     * @param BatchTableFactory $batchTableFactory
     */
    public function __construct(
        ResourceConnection  $resourceConnection,
        IteratorFactory     $iteratorFactory,
        IdIteratorFactory $idIteratorFactory,
        BatchLocatorFactory $batchLocatorFactory,
        BatchTableFactory   $batchTableFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->iteratorFactory = $iteratorFactory;
        $this->idIteratorFactory = $idIteratorFactory;
        $this->batchLocatorFactory = $batchLocatorFactory;
        $this->batchTableFactory = $batchTableFactory;
    }

    /**
     * @inheritDoc
     */
    public function generate(FeedIndexMetadata $metadata, array $args = []): BatchIteratorInterface
    {
        $sinceTimestamp = array_key_exists('sinceTimestamp', $args) ? (string)$args['sinceTimestamp'] : '1';
        $connection = $this->resourceConnection->getConnection();
        if ($connection instanceof \Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy) {
            $connection->setUseMasterConnection();
        }
        $sourceTableName = $this->resourceConnection->getTableName($metadata->getFeedTableName());
        $sourceTableKeyColumns = $metadata->isExportImmediately() ?
            [$metadata->getFeedTableField()] : $this->getPrimaryKeyColumns($sourceTableName);

        $sequenceTableName = $this->resourceConnection->getTableName(
            sprintf("%s_sync_sequence", $metadata->getFeedTableName())
        );
        $batchLocator = $this->batchLocatorFactory->create(['sequenceTableName' => $sequenceTableName]);
        $batchLocator->init();

        $batchTableName = $this->resourceConnection->getTableName(
            sprintf("%s_sync_batches", $metadata->getFeedTableName())
        );
        $batchTable = $this->batchTableFactory->create(
            [
                'batchTableName' => $batchTableName,
                'sourceTableName' => $sourceTableName,
                'sourceTableKeyColumns' => $sourceTableKeyColumns
            ]
        );
        $insertDataQuery = $connection->insertFromSelect(
            $this->getSelect(
                $sourceTableName,
                $sourceTableKeyColumns,
                $metadata->getBatchSize(),
                $batchTable->getBatchNumberField(),
                $sinceTimestamp,
                $metadata->isExportImmediately()
            ),
            $batchTable->getBatchTableName(),
            array_merge([$batchTable->getBatchNumberField()], $sourceTableKeyColumns)
        );
        $batchTable->create($insertDataQuery);

        if ($metadata->isExportImmediately()) {
            $batchIterator = $this->idIteratorFactory->create(
                [
                    'batchTable' => $batchTable,
                    'sourceTableKeyColumn' => $metadata->getFeedTableField(),
                    'batchLocator' => $batchLocator,
                    'dateTimeFormat' => $metadata->getDateTimeFormat()
                ]
            );
        } else {
            $batchIterator = $this->iteratorFactory->create(
                [
                    'batchTable' => $batchTable,
                    'sourceTableName' => $sourceTableName,
                    'sourceTableKeyColumns' => $sourceTableKeyColumns,
                    'batchLocator' => $batchLocator,
                    'dateTimeFormat' => $metadata->getDateTimeFormat(),
                    'isRemovable' => $metadata->isRemovable()
                ]
            );
        }

        return $batchIterator;
    }

    /**
     * Returns select to create batches.
     *
     * @param string $sourceTableName
     * @param array $sourceTableKeyColumns
     * @param int $batchSize
     * @param string $batchNumField
     * @param string $sinceTimestamp
     * @param bool $isExportImmediately
     * @return Select
     */
    private function getSelect(
        string $sourceTableName,
        array  $sourceTableKeyColumns,
        int    $batchSize,
        string $batchNumField,
        string $sinceTimestamp,
        bool   $isExportImmediately
    ): Select {
        $connection = $this->resourceConnection->getConnection();
        $sinceTimestamp = $sinceTimestamp === '1' ? (int)$sinceTimestamp : $sinceTimestamp;

        $subSelect = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['st' => $sourceTableName],
                $sourceTableKeyColumns
            )
            ->where('st.modified_at > ?', $sinceTimestamp);
        if ($isExportImmediately) {
            $subSelect->distinct(true);
            $subSelect->where('st.status NOT IN (?)', ExportStatusCodeProvider::NON_RETRYABLE_HTTP_STATUS_CODE);
        }

        $select = $connection->select()
            ->from(
                ['t' => new \Zend_Db_Expr((sprintf('(%s)', $subSelect)))],
                array_merge(
                    [
                        $batchNumField => new \Zend_Db_Expr(
                            sprintf(
                                "CEILING(ROW_NUMBER() OVER (ORDER BY %s) / %d)",
                                implode(', ', $sourceTableKeyColumns),
                                $batchSize
                            )
                        )
                    ],
                    $sourceTableKeyColumns
                )
            );

        return $select;
    }

    /**
     * Returns primary key columns for the given table.
     *
     * @param string $tableName
     * @return array
     */
    private function getPrimaryKeyColumns(string $tableName): array
    {
        $pkColumns = array_filter(
            $this->resourceConnection->getConnection()->describeTable($tableName),
            function ($column) {
                return $column['PRIMARY'];
            }
        );

        return array_keys($pkColumns);
    }
}
