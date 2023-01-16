<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model;

use Generator;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\BatchIteratorFactory;

class OnDemandExportOrdersRepository
{
    private FeedIndexMetadata $metadata;
    private ResourceConnection $resourceConnection;
    private BatchIteratorFactory $batchIteratorFactory;

    public function __construct(
        FeedIndexMetadata    $metadata,
        ResourceConnection   $resourceConnection,
        BatchIteratorFactory $batchIteratorFactory
    ) {
        $this->metadata = $metadata;
        $this->resourceConnection = $resourceConnection;
        $this->batchIteratorFactory = $batchIteratorFactory;
    }

    public function countOrders(): int
    {
        $tableName = $this->resourceConnection->getTableName($this->metadata->getFeedTableName());
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['order' => $tableName],
                ['count' => 'COUNT(*)']
            );
        $row = $connection->fetchRow($select);
        return $row['count'];
    }

    public function fetchOrders(int $batchSize = 100): Generator
    {
        $tableName = $this->resourceConnection->getTableName($this->metadata->getFeedTableName());
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['order' => $tableName],
                ['id', 'feed_data']
            );

        $iterator = $this->batchIteratorFactory->create(
            [
                'select' => $select,
                'batchSize' => $batchSize,
                'correlationName' => 'order',
                'rangeField' => 'id',
                'rangeFieldAlias' => 'id',
            ]
        );

        foreach ($iterator as $batchSelect) {
            yield $connection->fetchAll($batchSelect);
        }
    }
}
