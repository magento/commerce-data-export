<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Plugin\Catalog\Ui\DataProvider;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Ui\DataProvider\CatalogEavValidationRules;

/**
 * Adds validate-number rule for numeric input type so non-numeric values are
 * rejected in the admin product edit form (same behaviour as price).
 */
class CatalogEavValidationRulesPlugin
{
    private const FRONTEND_INPUT = 'numeric';

    /**
     * Add validate-number rule for numeric frontend input type.
     *
     * @param CatalogEavValidationRules $subject
     * @param array $result
     * @param ProductAttributeInterface $attribute
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterBuild(
        CatalogEavValidationRules $subject,
        array $result,
        ProductAttributeInterface $attribute
    ): array {
        if ($attribute->getFrontendInput() === self::FRONTEND_INPUT) {
            $result['validate-number'] = true;
        }
        return $result;
    }
}
