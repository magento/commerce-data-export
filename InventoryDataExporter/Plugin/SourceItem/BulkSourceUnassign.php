<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryDataExporter\Plugin\SourceItem;

use Magento\Indexer\Model\Indexer;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;

/**
 * Reindex stock status feed indexer if source item was unassigned in bulk operation
 */
class BulkSourceUnassign
{
    private const STOCK_STATUS_FEED_INDEXER = 'inventory_data_exporter_stock_status';

    /**
     * @var \Magento\Indexer\Model\Indexer
     */
    private $indexer;

    /**
     * @param \Magento\Indexer\Model\Indexer $indexer
     */
    public function __construct(
        Indexer $indexer
    ) {
        $this->indexer = $indexer;
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
        $stockStatusIndexer = $this->indexer->load(self::STOCK_STATUS_FEED_INDEXER);
        if (!$stockStatusIndexer->isScheduled()) {
            $stockStatusIndexer->reindexList($skus);
        }

        return $result;
    }
}
