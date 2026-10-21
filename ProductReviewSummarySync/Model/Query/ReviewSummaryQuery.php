<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Review\Model\Review;

/**
 * Queries on the native Magento review tables
 */
class ReviewSummaryQuery
{
    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Get product review summaries for the given products and store views.
     *
     * Summaries with 0 approved reviews are included, so that a product whose reviews were all unapproved
     * or deleted is exported with 0. The store_id = 0 row is never read: core does not reset it to 0.
     *
     * @param int[] $productIds
     * @param string[] $storeViewCodes
     * @return Select
     */
    public function getSummariesSelect(array $productIds, array $storeViewCodes): Select
    {
        return $this->resourceConnection->getConnection()->select()
            ->from(
                ['summary' => $this->resourceConnection->getTableName('review_entity_summary')],
                [
                    'productId' => 'summary.entity_pk_value',
                    'reviewCount' => 'summary.reviews_count',
                    'ratingSummary' => 'summary.rating_summary',
                ]
            )
            ->joinInner(
                ['entity' => $this->resourceConnection->getTableName('review_entity')],
                'entity.entity_id = summary.entity_type',
                []
            )
            ->joinInner(
                ['store' => $this->resourceConnection->getTableName('store')],
                'store.store_id = summary.store_id',
                ['storeViewCode' => 'store.code']
            )
            ->where('entity.entity_code = ?', Review::ENTITY_PRODUCT_CODE)
            ->where('summary.entity_pk_value IN (?)', $productIds)
            ->where('store.code IN (?)', $storeViewCodes);
    }

    /**
     * Get ids of all existing products that have, or had, reviews.
     *
     * Not limited to reviews_count > 0: products whose reviews were all unapproved or deleted must be
     * re-exported too, as well as products with reviews but without a summary row yet.
     *
     * @return Select
     */
    public function getReviewedProductIdsSelect(): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $summaryProducts = $connection->select()
            ->from(
                ['summary' => $this->resourceConnection->getTableName('review_entity_summary')],
                ['entity_id' => 'summary.entity_pk_value']
            )
            ->joinInner(
                ['entity' => $this->resourceConnection->getTableName('review_entity')],
                'entity.entity_id = summary.entity_type',
                []
            )
            ->where('entity.entity_code = ?', Review::ENTITY_PRODUCT_CODE);
        $reviewProducts = $connection->select()
            ->from(
                ['review' => $this->resourceConnection->getTableName('review')],
                ['entity_id' => 'review.entity_pk_value']
            )
            ->joinInner(
                ['entity' => $this->resourceConnection->getTableName('review_entity')],
                'entity.entity_id = review.entity_id',
                []
            )
            ->where('entity.entity_code = ?', Review::ENTITY_PRODUCT_CODE);

        return $connection->select()
            ->from(
                ['reviewed' => $connection->select()->union([$summaryProducts, $reviewProducts])],
                ['entity_id' => 'reviewed.entity_id']
            )
            ->joinInner(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'product.entity_id = reviewed.entity_id',
                []
            );
    }
}
