<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryDataExporter\Plugin\SourceItem;

use Magento\Indexer\Model\Indexer;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Reindex stock status feed indexer if source item updated
 */
class SourceItemUpdate
{
    private const STOCK_STATUS_FEED_INDEXER = 'inventory_data_exporter_stock_status';

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @param Indexer $indexer
     */
    public function __construct(
        Indexer $indexer
    ) {
        $this->indexer = $indexer;
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
    ): void
    {
        $stockStatusIndexer = $this->indexer->load(self::STOCK_STATUS_FEED_INDEXER);
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
