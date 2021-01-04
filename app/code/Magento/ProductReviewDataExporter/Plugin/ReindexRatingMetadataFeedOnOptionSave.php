<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\ProductReviewDataExporter\Model\Indexer\ReindexOnSaveAction;
use Magento\Review\Model\Rating\Option;

/**
 * Plugin for running rating metadata feed indexation after rating option save operation
 */
class ReindexRatingMetadataFeedOnOptionSave
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
     * Execute reindex process on save commit callback
     *
     * @param Option $subject
     *
     * @return Option
     */
    public function beforeAfterCommitCallback(Option $subject): Option
    {
        $this->reindexOnSaveAction->execute(
            ReindexOnSaveAction::RATING_METADATA_FEED_INDEXER,
            [$subject->getRatingId()]
        );

        return $subject;
    }
}
