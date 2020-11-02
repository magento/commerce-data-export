<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Review\Model\Rating\Option;
use Magento\Review\Model\ResourceModel\Rating\Option as RatingOptionResource;

/**
 * Plugin for running review feed indexation during review vote add
 */
class ReindexReviewFeedOnVoteAdd
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
     * Execute review reindex process on add vote operation
     *
     * @param RatingOptionResource $subject
     * @param RatingOptionResource $result
     * @param Option $option
     *
     * @return RatingOptionResource
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddVote(
        RatingOptionResource $subject,
        RatingOptionResource $result,
        Option $option
    ): RatingOptionResource {
        $indexer = $this->indexerRegistry->get(self::REVIEW_FEED_INDEXER);
        if (!$indexer->isScheduled()) {
            $indexer->reindexRow($option->getReviewId());
        }

        return $result;
    }
}
