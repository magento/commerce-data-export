<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Review\Model\ResourceModel\Review as ReviewResource;

/**
 * Plugin for marking removed reviews in feed after product removal
 */
class MarkRemovedReviewsOnProductDelete
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
     * @var FeedIndexMetadata
     */
    private $feedIndexMetadata;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        FeedIndexMetadata $feedIndexMetadata,
        ResourceConnection $resourceConnection
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->resourceConnection = $resourceConnection;
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
        $indexer = $this->indexerRegistry->get(self::REVIEW_FEED_INDEXER);
        if (!$indexer->isScheduled()) {
            $indexer->reindexList($this->fetchReviewIdsByProductId($productId));
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
