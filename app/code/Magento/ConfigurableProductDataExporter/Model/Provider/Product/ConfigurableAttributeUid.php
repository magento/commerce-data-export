<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product;

/**
 * Format new option uid in base64 encode for super attribute options
 */
class ConfigurableAttributeUid
{
    /**
     * Option type name
     */
    public const OPTION_TYPE = 'configurable';

    /**
     * Returns uid based on attribute id and option value index
     *
     * @param string $attributeId
     * @param string $valueIndex
     * @return string
     */
    public function resolve(string $attributeId, string $valueIndex): string
    {
        $optionDetails = [
            self::OPTION_TYPE,
            $attributeId,
            $valueIndex
        ];

        $content = implode('/', $optionDetails);

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return \base64_encode($content);
    }
}
