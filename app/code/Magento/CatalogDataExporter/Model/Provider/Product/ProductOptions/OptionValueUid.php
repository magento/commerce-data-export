<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions;

/**
 * Product option value uid generator
 */
class OptionValueUid
{
    /**
     * Selectable Option type name
     */
    private const OPTION_TYPE = 'custom-option';

    /**
     * Returns uid based on option id and option value id
     *
     * @param string $optionId
     * @param string $optionValueId
     * @return string
     */
    public function resolve(string $optionId, string $optionValueId): string
    {
        $optionDetails = [
            self::OPTION_TYPE,
            $optionId,
            $optionValueId
        ];

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return \base64_encode(\implode('/', $optionDetails));
    }
}
