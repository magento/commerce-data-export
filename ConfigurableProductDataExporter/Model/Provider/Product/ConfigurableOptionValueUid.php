<?php
/**
 * Copyright 2021 Adobe
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

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product;

/**
 * Create configurable product option value uid in base64 encode
 */
class ConfigurableOptionValueUid
{
    /**
     * Option type name
     */
    public const OPTION_TYPE = 'configurable';

    /**
     * Separator of uid for encoding
     */
    private const UID_SEPARATOR = '/';

    /**
     * Returns uid based on attribute id and option value
     *
     * @param string $attributeId
     * @param string $valueIndex
     * @return string
     * @see \Magento\ConfigurableProductGraphQl\Model\Options\SelectionUidFormatter::encode
     */
    public function resolve(string $attributeId, string $valueIndex): string
    {
        $optionDetails = [
            self::OPTION_TYPE,
            $attributeId,
            $valueIndex
        ];

        $content = implode(self::UID_SEPARATOR, $optionDetails);

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return \base64_encode($content);
    }
}
