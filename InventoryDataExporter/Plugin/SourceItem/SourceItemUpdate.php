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
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Reindex stock status feed indexer if source item updated
 */
class SourceItemUpdate
{
    private const STOCK_STATUS_FEED_INDEXER = 'inventory_data_exporter_stock_status';

    private IndexerRegistry $indexerRegistry;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->logger = $logger;
    }

    /**
     * @param SourceItemsSaveInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        SourceItemsSaveInterface $subject,
        $result,
        array $sourceItems
    ): void {
        try {
            $stockStatusIndexer = $this->indexerRegistry->get(self::STOCK_STATUS_FEED_INDEXER);
            if (!$stockStatusIndexer->isScheduled()) {
                $skus = \array_map(
                    static function (SourceItemInterface $sourceItem) {
                        return $sourceItem->getSku();
                    },
                    $sourceItems
                );
                $stockStatusIndexer->reindexList($skus);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
