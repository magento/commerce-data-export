<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Model;

use AdobeCommerce\ProductReviewSummarySync\Model\Query\ReviewSummaryQuery;

/**
 * Re-export reviewed products so the review summary values in the products feed match the reviews.
 */
class ReviewSummaryResync
{
    /**
     * @param ReviewSummaryQuery $reviewSummaryQuery
     * @param ProductFeedUpdateScheduler $productFeedUpdateScheduler
     */
    public function __construct(
        private readonly ReviewSummaryQuery $reviewSummaryQuery,
        private readonly ProductFeedUpdateScheduler $productFeedUpdateScheduler
    ) {
    }

    /**
     * Schedule all products that have or had reviews for re-export.
     *
     * @return void
     */
    public function reconcile(): void
    {
        $this->productFeedUpdateScheduler->scheduleFromSelect($this->reviewSummaryQuery->getReviewedProductIdsSelect());
    }
}
