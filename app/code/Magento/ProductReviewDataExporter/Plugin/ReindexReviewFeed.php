<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Review\Model\Review;

/**
 * Plugin for running review feed indexation during save / delete operation
 */
class ReindexReviewFeed
{
    /**
     * Review feed indexer id
     */
    private const REVIEW_FEED_INDEXER = 'catalog_data_exporter_product_reviews';

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
     * Execute reindex process on delete callback
     *
     * @param Review $subject
     *
     * @return Review
     */
    public function beforeAfterDeleteCommit(Review $subject): Review
    {
        $this->reindex($subject);

        return $subject;
    }

    /**
     * Execute reindex process on save commit callback
     *
     * @param Review $subject
     *
     * @return Review
     */
    public function beforeAfterCommitCallback(Review $subject): Review
    {
        $this->reindex($subject);

        return $subject;
    }

    /**
     * Re-indexation process of review feed
     *
     * @param Review $review
     *
     * @return void
     */
    public function reindex(Review $review): void
    {
        $indexer = $this->indexerRegistry->get(self::REVIEW_FEED_INDEXER);
        if (!$indexer->isScheduled()) {
            $indexer->reindexRow($review->getId());
        }
    }
}
