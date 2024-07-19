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
        try {
            $this->reindexOnSaveAction->execute(ReindexOnSaveAction::REVIEW_FEED_INDEXER, [$option->getReviewId()]);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

        return $result;
    }
}
