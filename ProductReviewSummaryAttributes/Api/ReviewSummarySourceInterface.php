<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Api;

/**
 * Source of product review summaries exported as the ac_review_count / ac_rating_summary attributes.
 *
 * The default implementation is disabled and returns no data, so the attributes are neither created nor
 * exported. A review provider (e.g. native Magento reviews) enables the feature by overriding the
 * preference for this interface.
 *
 * @api
 */
interface ReviewSummarySourceInterface
{
    /**
     * Whether review summaries should be exported.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Get review summaries for the given products and store views.
     *
     * Result is keyed by \AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract::key().
     * Products without a summary must be omitted. A summary with reviewCount = 0 is still returned
     * when it exists, so that a product whose reviews were all unapproved or deleted is re-exported with 0.
     *
     * @param int[] $productIds
     * @param string[] $storeViewCodes
     * @return array<string, array{reviewCount: int, ratingSummary: float}>
     */
    public function getSummaries(array $productIds, array $storeViewCodes): array;
}
