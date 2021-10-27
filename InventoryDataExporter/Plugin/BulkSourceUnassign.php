<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryDataExporter\Plugin;

use Magento\InventoryDataExporter\Model\Provider\StockStatusIdBuilder;
use Magento\InventoryDataExporter\Model\Query\StockStatusDeleteQuery;

/**
 * Mark stock statuses as deleted on bulk unassign
 */
class BulkSourceUnassign
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
     * Check which stocks will be unassigned from products and mark them as deleted in feed table
     *
     * @param \Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface $subject
     * @param array $skus
     * @param array $sourceCodes
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        \Magento\InventoryCatalog\Model\ResourceModel\BulkSourceUnassign $subject,
        array $skus,
        array $sourceCodes
    ): void {
        $fetchedSourceItems = $this->stockStatusDeleteQuery->getStocksAssignedToSkus($skus);
        $stocksToDelete = $this->getStocksToDelete($skus, $sourceCodes, $fetchedSourceItems);

        if (!empty($stocksToDelete)) {
            $this->stockStatusDeleteQuery->markStockStatusesAsDeleted($stocksToDelete);
        }
    }

    /**
     * @param array $affectedSkus
     * @param array $deletedSources
     * @return array
     */
    private function getStocksToDelete(array $affectedSkus, array $deletedSources, $fetchedSourceItems): array
    {
        $stocksToDelete = [];
        foreach ($affectedSkus as $deletedItemSku) {
            foreach ($fetchedSourceItems[$deletedItemSku] as $fetchedItemStockId => $fetchedItemSources) {
                if ($this->getContainsAllKeys($fetchedItemSources, $deletedSources)) {
                    $stocksToDelete[] = StockStatusIdBuilder::build(
                        ['stockId' => (string)$fetchedItemStockId, 'sku' => $deletedItemSku]
                    );
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
}
