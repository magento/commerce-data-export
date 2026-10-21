<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Model\Source;

use AdobeCommerce\ProductReviewSummaryAttributes\Api\ReviewSummarySourceInterface;

/**
 * Default review summary source: review summaries are not exported until a real source is configured.
 */
class DisabledReviewSummarySource implements ReviewSummarySourceInterface
{
    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getSummaries(array $productIds, array $storeViewCodes): array
    {
        return [];
    }
}
