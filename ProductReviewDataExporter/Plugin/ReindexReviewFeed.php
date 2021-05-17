<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\ProductReviewDataExporter\Model\Indexer\ReindexOnSaveAction;
use Magento\Review\Model\Review;

/**
 * Plugin for running review feed indexation during save / delete operation
 */
class ReindexReviewFeed
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
     * Execute reindex process on delete callback
     *
     * @param Review $subject
     *
     * @return Review
     */
    public function beforeAfterDeleteCommit(Review $subject): Review
    {
        $this->reindexOnSaveAction->execute(ReindexOnSaveAction::REVIEW_FEED_INDEXER, [$subject->getId()]);

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
        $this->reindexOnSaveAction->execute(ReindexOnSaveAction::REVIEW_FEED_INDEXER, [$subject->getId()]);

        return $subject;
    }
}
