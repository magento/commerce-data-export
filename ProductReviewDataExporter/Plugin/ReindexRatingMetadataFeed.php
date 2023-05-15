<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\ProductReviewDataExporter\Model\Indexer\ReindexOnSaveAction;
use Magento\Review\Model\Rating;

/**
 * Plugin for running rating metadata feed indexation during save / delete operation
 */
class ReindexRatingMetadataFeed
{
    private ReindexOnSaveAction $reindexOnSaveAction;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ReindexOnSaveAction $reindexOnSaveAction
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ReindexOnSaveAction $reindexOnSaveAction,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->reindexOnSaveAction = $reindexOnSaveAction;
        $this->logger = $logger;
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
        try {
            $this->reindexOnSaveAction->execute(ReindexOnSaveAction::RATING_METADATA_FEED_INDEXER, [$subject->getId()]);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

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
        try {
            $this->reindexOnSaveAction->execute(ReindexOnSaveAction::RATING_METADATA_FEED_INDEXER, [$subject->getId()]);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

        return $subject;
    }
}
