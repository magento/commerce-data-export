<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryDataExporter\Plugin;

use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryDataExporter\Model\Provider\StockStatusIdBuilder;
use Magento\InventoryDataExporter\Model\Query\StockStatusDeleteQuery;

/**
 * Plugin for setting stock item statuses as deleted
 */
class MarkItemsAsDeleted
{
    /**
     * @var StockStatusDeleteQuery
     */
    private $stockStatusDeleteQuery;

    /**
     * @param StockStatusDeleteQuery $stockStatusDeleteQuery
     */
    public function __construct(
        StockStatusDeleteQuery $stockStatusDeleteQuery
    ) {
        $this->stockStatusDeleteQuery = $stockStatusDeleteQuery;
    }

    /**
     * Set is_deleted value to 1 for deleted stock statuses
     *
     * @param DeleteMultiple $subject
     * @param SourceItemInterface[] $sourceItems
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        DeleteMultiple $subject,
        array $sourceItems
    ): void {
        $deletedSourceItems = [];
        foreach ($sourceItems as $sourceItem) {
            $deletedSourceItems[$sourceItem->getSku()][] = $sourceItem->getSourceCode();
        }

        $fetchedSourceItems = $this->stockStatusDeleteQuery->getStocksAssignedToSkus(array_keys($deletedSourceItems));

        if (!empty($fetchedSourceItems)) {
            $stocksToDelete = $this->getStocksToDelete($deletedSourceItems, $fetchedSourceItems);
        }
        if (!empty($stocksToDelete)) {
            $this->stockStatusDeleteQuery->markStockStatusesAsDeleted($stocksToDelete);
        }
    }

    /**
     * Get stocks to delete
     *
     * @param array $deletedSourceItems
     * @param array $fetchedSourceItems
     * @return array
     */
    private function getStocksToDelete(array $deletedSourceItems, array $fetchedSourceItems): array
    {
        $stocksToDelete = [];
        foreach ($deletedSourceItems as $deletedItemSku => $deletedItemSources) {
            foreach ($fetchedSourceItems[$deletedItemSku] as $fetchedItemStockId => $fetchedItemSources) {
                if ($this->isContainsAllKeys($fetchedItemSources, $deletedItemSources)) {
                    $stockStatusId = StockStatusIdBuilder::build(
                        ['stockId' => (string)$fetchedItemStockId, 'sku' => $deletedItemSku]
                    );
                    $stocksToDelete[$stockStatusId] = [
                        'stock_id' => (string)$fetchedItemStockId,
                        'sku' => $deletedItemSku
                    ];
                }
            }
        }

        return $stocksToDelete;
    }

    /**
     * Is contains all keys
     *
     * @param array $fetchedSources
     * @param array $deletedSources
     * @return bool
     */
    private function isContainsAllKeys(array $fetchedSources, array $deletedSources): bool
    {
        return empty(\array_diff($fetchedSources, $deletedSources));
    }
}
