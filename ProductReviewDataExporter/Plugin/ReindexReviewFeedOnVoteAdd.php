<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
