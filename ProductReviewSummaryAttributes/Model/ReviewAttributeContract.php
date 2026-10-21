<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Model;

use AdobeCommerce\CatalogProductNumericAttribute\Model\InputType;
use Magento\Framework\DataObject;

/**
 * Review summary attribute codes shared with Catalog Service and the storefront.
 *
 * The codes are a public contract and must not be renamed.
 */
class ReviewAttributeContract
{
    /**
     * Number of approved reviews
     */
    public const ATTRIBUTE_REVIEW_COUNT = 'ac_review_count';

    /**
     * Average rating as a percentage (0-100)
     */
    public const ATTRIBUTE_RATING_SUMMARY = 'ac_rating_summary';

    public const ATTRIBUTE_CODES = [
        self::ATTRIBUTE_REVIEW_COUNT,
        self::ATTRIBUTE_RATING_SUMMARY,
    ];

    /**
     * Build the key used to match review summaries with product feed items.
     *
     * @param int|string $productId
     * @param string $storeViewCode
     * @return string
     */
    public function key(int|string $productId, string $storeViewCode): string
    {
        return $productId . '-' . $storeViewCode;
    }

    /**
     * Check whether the attribute is one of the review summary system attributes.
     *
     * Attributes created by merchants with the same code are user defined: Admin and the attribute repository
     * always set "is_user_defined" on new attributes. The review summary attributes always use the Number input.
     *
     * @param DataObject $attribute
     * @return bool
     */
    public function isReviewSummaryAttribute(DataObject $attribute): bool
    {
        return in_array($attribute->getData('attribute_code'), self::ATTRIBUTE_CODES, true)
            && (int)$attribute->getData('is_user_defined') === 0
            && $attribute->getData('frontend_input') === InputType::NUMERIC;
    }
}
