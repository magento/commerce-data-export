<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\ProductReviewDataExporter\Model\Indexer\ReindexOnSaveAction;
use Magento\Review\Model\Rating;

/**
 * Plugin for running rating metadata feed indexation during save / delete operation
 */
class ReindexRatingMetadataFeed
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
     * @param Rating $subject
     *
     * @return Rating
     */
    public function beforeAfterDeleteCommit(Rating $subject): Rating
    {
        $this->reindexOnSaveAction->execute(ReindexOnSaveAction::RATING_METADATA_FEED_INDEXER, [$subject->getId()]);

        return $subject;
    }

    /**
     * Execute reindex process on save commit callback
     *
     * @param Rating $subject
     *
     * @return Rating
     */
    public function beforeAfterCommitCallback(Rating $subject): Rating
    {
        $this->reindexOnSaveAction->execute(ReindexOnSaveAction::RATING_METADATA_FEED_INDEXER, [$subject->getId()]);

        return $subject;
    }
}
