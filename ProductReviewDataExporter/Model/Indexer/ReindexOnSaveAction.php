<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Model\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Action responsible for execution of re-index process for specific indexer and entity ids
 */
class ReindexOnSaveAction
{
    /**
     * Review feed indexer id
     */
    public const REVIEW_FEED_INDEXER = 'catalog_data_exporter_product_reviews';

    /**
     * Rating metadata feed indexer id
     */
    public const RATING_METADATA_FEED_INDEXER = 'catalog_data_exporter_rating_metadata';

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
     * Execute re-indexation action
     *
     * @param string $feedIndexer
     * @param int[] $ids
     *
     * @return void
     */
    public function execute(string $feedIndexer, array $ids): void
    {
        $indexer = $this->indexerRegistry->get($feedIndexer);

        if (!$indexer->isScheduled()) {
            $indexer->reindexList($ids);
        }
    }
}
