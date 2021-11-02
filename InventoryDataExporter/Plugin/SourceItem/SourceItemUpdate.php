<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryDataExporter\Plugin\SourceItem;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Reindex stock status feed indexer if source item updated
 */
class SourceItemUpdate
{
    private const STOCK_STATUS_FEED_INDEXER = 'inventory_data_exporter_stock_status';

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexerRegistry = $indexerRegistry;
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
    }
}
