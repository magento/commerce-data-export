<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Plugin\Ui\Component;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Ui\Component\ColumnFactory;

/**
 * Uses from/to range filters for Number (numeric) attributes in the admin product grid.
 *
 * Core Listing\Columns maps unknown frontend_input values to a plain text filter; numeric must
 * use textRange (same as Price) so Magento_Ui renders filterRange with from/to fields.
 */
class ColumnFactoryPlugin
{
    private const FRONTEND_INPUT_NUMERIC = 'numeric';

    private const FILTER_TEXT_RANGE = 'textRange';

    /**
     * Set textRange filter for numeric attributes in the admin product grid.
     *
     * @param ColumnFactory $subject
     * @param mixed $attribute
     * @param mixed $context
     * @param array $config
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCreate(
        ColumnFactory $subject,
        mixed $attribute,
        mixed $context,
        array $config = []
    ): array {
        if ($attribute instanceof ProductAttributeInterface
            && $attribute->getFrontendInput() === self::FRONTEND_INPUT_NUMERIC
            && $attribute->getIsFilterableInGrid()
        ) {
            $config['filter'] = self::FILTER_TEXT_RANGE;
        }

        return [$attribute, $context, $config];
    }
}
