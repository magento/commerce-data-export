<?php
/**
 * Copyright 2024 Adobe
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
            $unassignedProducts = $this->stockStatusDeleteQuery->getProductIdsForSkus($skus);
            $stocksToDelete = $this->getStocksToDelete($unassignedProducts, $sourcesByStocks, $sourcesAssignedToProducts);

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
     * @param array $stockData
     * @return array
     */
    private function getStocksToDelete(
        array $affectedSkus,
        array $sourcesByStocks,
        array $sourcesAssignedToProducts
    ): array {
        $stocksToDelete = [];
        foreach ($affectedSkus as $deletedItemSku => $deletedItemProductId) {
            foreach (array_keys($sourcesByStocks) as $stockId) {
                $stockStatusId = StockStatusIdBuilder::build(
                    ['stockId' => (string)$stockId, 'sku' => $deletedItemSku]
                );
                $stocksToDelete[$stockStatusId] = [
                    'stockId' => (string)$stockId,
                    'productId' => $deletedItemProductId,
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
