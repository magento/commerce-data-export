<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryDataExporter\Plugin;

use Magento\InventoryDataExporter\Model\Provider\StockStatusIdBuilder;
use Magento\InventoryDataExporter\Model\Query\StockStatusDeleteQuery;

/**
 * Mark stock statuses as deleted on bulk unassign only when all sources was unassigned from the same stock
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
     * @param \Magento\InventoryCatalog\Model\ResourceModel\BulkSourceUnassign $subject
     * @param int $result
     * @param array $skus
     * @param array $sourceCodes
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        \Magento\InventoryCatalog\Model\ResourceModel\BulkSourceUnassign $subject,
        int $result,
        array $skus,
        array $sourceCodes
    ): int {
        $sourcesAssignedToProducts = $this->stockStatusDeleteQuery->getStocksAssignedToSkus($skus);
        $sourcesByStocks = $this->stockStatusDeleteQuery->getStocksWithSources($sourceCodes);
        $stocksToDelete = $this->getStocksToDelete($skus, $sourcesByStocks, $sourcesAssignedToProducts);

        if (!empty($stocksToDelete)) {
            $this->stockStatusDeleteQuery->markStockStatusesAsDeleted($stocksToDelete);
        }

        return $result;
    }

    /**
     * @param array $affectedSkus
     * @param array $sourcesByStocks
     * @param array $sourcesAssignedToProducts
     * @return array
     */
    private function getStocksToDelete(
        array $affectedSkus,
        array $sourcesByStocks,
        array $sourcesAssignedToProducts
    ): array {
        $stocksToDelete = [];
        foreach ($affectedSkus as $deletedItemSku) {
            foreach (array_keys($sourcesByStocks) as $stockId) {
                $stocksToDelete[] = StockStatusIdBuilder::build(
                    ['stockId' => (string)$stockId, 'sku' => $deletedItemSku]
                );
            }
            if (!isset($sourcesAssignedToProducts[$deletedItemSku])) {
                continue ;
            }

            foreach ($sourcesAssignedToProducts[$deletedItemSku] as $fetchedItemStockId => $fetchedItemSources) {
                if ($this->getContainsAllKeys($fetchedItemSources, $sourcesByStocks[$fetchedItemStockId])) {
                    $stockStatusId = StockStatusIdBuilder::build(
                        ['stockId' => (string)$fetchedItemStockId, 'sku' => $deletedItemSku]
                    );
                    if ($key = \array_search($stockStatusId, $stocksToDelete, false)) {
                        unset($stocksToDelete[(int)$key]);
                    }
                }
            }
        }

        return array_filter($stocksToDelete);
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
