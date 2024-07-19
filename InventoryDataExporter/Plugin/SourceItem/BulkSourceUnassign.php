<?php
/**
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
 */
namespace Magento\InventoryDataExporter\Plugin\SourceItem;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;

/**
 * Reindex stock status feed indexer if source item was unassigned in bulk operation
 */
class BulkSourceUnassign
{
    private const STOCK_STATUS_FEED_INDEXER = 'inventory_data_exporter_stock_status';

    private IndexerRegistry $indexerRegistry;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        IndexerRegistry                   $indexerRegistry,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->logger = $logger;
    }

    /**
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
            $stockStatusIndexer = $this->indexerRegistry->get(self::STOCK_STATUS_FEED_INDEXER);
            if (!$stockStatusIndexer->isScheduled()) {
                $stockStatusIndexer->reindexList($skus);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
        return $result;
    }
}
