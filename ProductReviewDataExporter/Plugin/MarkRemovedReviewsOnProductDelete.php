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

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\ProductReviewDataExporter\Model\Indexer\ReindexOnSaveAction;
use Magento\Review\Model\ResourceModel\Review as ReviewResource;

/**
 * Plugin for marking removed reviews in feed after product removal
 */
class MarkRemovedReviewsOnProductDelete
{
    private ReindexOnSaveAction $reindexOnSaveAction;
    private FeedIndexMetadata $feedIndexMetadata;
    private ResourceConnection $resourceConnection;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ReindexOnSaveAction $reindexOnSaveAction
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param ResourceConnection $resourceConnection
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ReindexOnSaveAction $reindexOnSaveAction,
        FeedIndexMetadata $feedIndexMetadata,
        ResourceConnection $resourceConnection,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->reindexOnSaveAction = $reindexOnSaveAction;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Execute marking removed reviews after product removal
     *
     * @param ReviewResource $subject
     * @param ReviewResource $result
     * @param int $productId
     *
     * @return ReviewResource
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDeleteReviewsByProductId(
        ReviewResource $subject,
        ReviewResource $result,
        int $productId
    ): ReviewResource {
        try {
            $this->reindexOnSaveAction->execute(
                ReindexOnSaveAction::REVIEW_FEED_INDEXER,
                $this->fetchReviewIdsByProductId($productId)
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
        return $result;
    }

    /**
     * Fetch existing review ids from feed by product id
     *
     * @param int $productId
     *
     * @return array
     */
    private function fetchReviewIdsByProductId(int $productId): array
    {
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['f' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                [$this->feedIndexMetadata->getFeedTableField()]
            )->where('f.product_id = ?', $productId);

        return $this->resourceConnection->getConnection()->fetchCol($select);
    }
}
