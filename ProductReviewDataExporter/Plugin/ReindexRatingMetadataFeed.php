<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
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
