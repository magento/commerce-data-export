<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryDataExporter\Plugin;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;
use Magento\InventoryDataExporter\Model\Provider\StockStatusIdBuilder;
use Magento\InventoryDataExporter\Model\Query\StockStatusDeleteQuery;

/**
 * Mark stock statuses as deleted on bulk unassign only when all sources was unassigned from the same stock
 */
class BulkSourceUnassign
{
    private StockStatusDeleteQuery $stockStatusDeleteQuery;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param StockStatusDeleteQuery $stockStatusDeleteQuery
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        StockStatusDeleteQuery $stockStatusDeleteQuery,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->stockStatusDeleteQuery = $stockStatusDeleteQuery;
        $this->logger = $logger;
    }

    /**
     * Check which stocks will be unassigned from products and mark them as deleted in feed table
     *
     * @param BulkSourceUnassignInterface $subject
     * @param int $result
     * @param array $skus
     * @param array $sourceCodes
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        BulkSourceUnassignInterface $subject,
        int $result,
        array $skus,
        array $sourceCodes
    ): int {
        try {
            $sourcesAssignedToProducts = $this->stockStatusDeleteQuery->getStocksAssignedToSkus($skus);
            $sourcesByStocks = $this->stockStatusDeleteQuery->getStocksWithSources($sourceCodes);
            $stocksToDelete = $this->getStocksToDelete($skus, $sourcesByStocks, $sourcesAssignedToProducts);

            if (!empty($stocksToDelete)) {
                $this->stockStatusDeleteQuery->markStockStatusesAsDeleted($stocksToDelete);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Bulk source unassign error', ['exception' => $e]);
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
                $stockStatusId = StockStatusIdBuilder::build(
                    ['stockId' => (string)$stockId, 'sku' => $deletedItemSku]
                );
                $stocksToDelete[$stockStatusId] = [
                    'stock_id' => (string)$stockId,
                    'sku' => $deletedItemSku
                ];
            }
            if (!isset($sourcesAssignedToProducts[$deletedItemSku])) {
                continue ;
            }

            foreach ($sourcesAssignedToProducts[$deletedItemSku] as $fetchedItemStockId => $fetchedItemSources) {
                if (isset($sourcesByStocks[$fetchedItemStockId])
                    && $this->isContainsAllKeys($fetchedItemSources, $sourcesByStocks[$fetchedItemStockId])) {
                    $stockStatusId = StockStatusIdBuilder::build(
                        ['stockId' => (string)$fetchedItemStockId, 'sku' => $deletedItemSku]
                    );
                    unset($stocksToDelete[$stockStatusId]);
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
    private function isContainsAllKeys(array $fetchedSources, array $deletedSources): bool
    {
        return empty(\array_diff($fetchedSources, $deletedSources));
    }
}
