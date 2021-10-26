<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryDataExporter\Plugin;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Plugin for setting stock item statuses as deleted
 */
class MarkItemsAsDeleted
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
     * Set is_deleted value to 1 for deleted stock statuses
     *
     * @param DeleteMultiple $subject
     * @param callable $proceed
     * @param SourceItemInterface[] $sourceItems
     * @return void
     */
    public function aroundExecute(
        DeleteMultiple $subject,
        callable $proceed,
        array $sourceItems
    ) {
        $deletedSourceItems = [];
        foreach ($sourceItems as $sourceItem) {
            $deletedSourceItems[$sourceItem->getSku()][] = $sourceItem->getSourceCode();
        }

        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['idess' => $this->resourceConnection->getTableName($this->metadata->getFeedTableName())],
                ['idess.sku', 'idess.stock_id', 'issl.source_code']
            )
            ->joinLeft(
                ['issl' => $this->resourceConnection->getTableName('inventory_source_stock_link')],
                'issl.stock_id = idess.stock_id'
            )
            ->joinInner(
                ['isi' => $this->resourceConnection->getTableName($this->metadata->getSourceTableName())],
                'isi.source_code = issl.source_code AND isi.sku = idess.sku',
                []
            )
            ->where('idess.sku IN (?)', array_keys($deletedSourceItems));

        $fetchedSourceItems = [];
        foreach ($connection->fetchAll($select) as $sourceItem) {
            $fetchedSourceItems[$sourceItem['sku']][$sourceItem['stock_id']][] = $sourceItem['source_code'];
        }
        $stocksToDelete = $this->getStocksToDelete($deletedSourceItems, $fetchedSourceItems);
        $proceed($sourceItems);
        if (!empty($stocksToDelete)) {
            $this->markStockStatusesAsDeleted($stocksToDelete);
        }
    }

    /**
     * @param array $deletedSourceItems
     * @param $fetchedSourceItems
     * @return array
     */
    private function getStocksToDelete(array $deletedSourceItems, $fetchedSourceItems): array
    {
        $stocksToDelete = [];
        foreach ($deletedSourceItems as $deletedItemSku => $deletedItemSources) {
            foreach ($fetchedSourceItems[$deletedItemSku] as $fetchedItemStockId => $fetchedItemSources) {
                if ($this->getContainsAllKeys($fetchedItemSources, $deletedItemSources)) {
                    $stocksToDelete['skus'][] = $deletedItemSku;
                    $stocksToDelete['stock_ids'][] = (string)$fetchedItemStockId;
                }
            }
        }

        return $stocksToDelete;
    }

    /**
     * @param array $fetchedSources
     * @param array $deletedSources
     * @return bool
     */
    private function getContainsAllKeys(array $fetchedSources, array $deletedSources): bool
    {
        return empty(\array_diff($fetchedSources, $deletedSources));
    }

    /**
     * @param array $stocksToDelete
     */
    private function markStockStatusesAsDeleted(array $stocksToDelete): void
    {
        $connection = $this->resourceConnection->getConnection();
        $feedTableName = $this->resourceConnection->getTableName($this->metadata->getFeedTableName());

        $update = $connection->update(
            $feedTableName,
            ['is_deleted' => new \Zend_Db_Expr('1')],
            [
                'sku IN (?)' => $stocksToDelete['skus'],
                'stock_id IN (?)' => $stocksToDelete['stock_ids']
            ]
        );

        $connection->query($update);
    }
}
