<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
     * After plugin for SourceItemsSaveInterface::execute - trigger stock status reindex when source items change.
     *
     * @param SourceItemsSaveInterface $subject
     * @param mixed $result
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
                    static fn(SourceItemInterface $sourceItem) => $sourceItem->getSku(),
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
