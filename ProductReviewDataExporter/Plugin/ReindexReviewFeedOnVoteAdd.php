<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\ProductReviewDataExporter\Model\Indexer\ReindexOnSaveAction;
use Magento\Review\Model\Rating\Option;
use Magento\Review\Model\ResourceModel\Rating\Option as RatingOptionResource;

/**
 * Plugin for running review feed indexation during review vote add
 */
class ReindexReviewFeedOnVoteAdd
{
    /**
     * @var ReindexOnSaveAction
     */
    private $reindexOnSaveAction;

    /**
     * @param ReindexOnSaveAction $reindexOnSaveAction
     */
    public function __construct(
        ReindexOnSaveAction $reindexOnSaveAction
    ) {
        $this->reindexOnSaveAction = $reindexOnSaveAction;
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
        $this->reindexOnSaveAction->execute(ReindexOnSaveAction::REVIEW_FEED_INDEXER, [$option->getReviewId()]);

        return $result;
    }
}
