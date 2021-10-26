<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;

/**
 * Stock Status mark as deleted query builder
 */
class StockStatusDeleteQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var FeedIndexMetadata
     */
    private $metadata;

    /**
     * @param ResourceConnection $resourceConnection
     * @param FeedIndexMetadata $metadata
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        FeedIndexMetadata $metadata
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadata = $metadata;
    }

    /**
     * Get stocks which are assigned to the list of provided SKUs
     *
     * @param array $skus
     * @return array
     */
    public function getStocksAssignedToSkus(array $skus): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['source_item' => $this->resourceConnection->getTableName('inventory_source_item')],
                ['source_item.sku', 'source_stock_link.stock_id']
            )->joinLeft(
                ['source_stock_link' => $this->resourceConnection->getTableName('inventory_source_stock_link')],
                'source_item.source_code = source_stock_link.source_code'
            )->where('source_item.sku IN (?)', $skus);

        $fetchedSourceItems = [];
        foreach ($connection->fetchAll($select) as $sourceItem) {
            $fetchedSourceItems[$sourceItem['sku']][$sourceItem['stock_id']][] = $sourceItem['source_code'];
        }

        return $fetchedSourceItems;
    }

    /**
     * Mark stock statuses as deleted
     *
     * @param array $stocksToDelete
     */
    public function markStockStatusesAsDeleted(array $stocksToDelete): void
    {
        $connection = $this->resourceConnection->getConnection();
        $feedTableName = $this->resourceConnection->getTableName($this->metadata->getFeedTableName());
        foreach ($stocksToDelete as $stockId => $skus) {
            $connection->update(
                $feedTableName,
                ['is_deleted' => new \Zend_Db_Expr('1')],
                [
                    'sku IN (?)' => $skus,
                    'stock_id = ?' => $stockId
                ]
            );
        }
    }
}
