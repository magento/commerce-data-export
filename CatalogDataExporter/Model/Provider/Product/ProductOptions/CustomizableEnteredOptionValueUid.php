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

namespace Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions;

/**
 * Customizable entered option value uid generator
 */
class CustomizableEnteredOptionValueUid implements OptionValueUidInterface
{
    /**
     * Customizable Option type name
     */
    private const OPTION_TYPE = 'custom-option';

    /**
     * Option id key
     */
    public const OPTION_ID = 'optionId';

    /**
     * Returns uid based on option id
     *
     * @param string[] $params
     *
     * @return string
     */
    public function resolve(array $params): string
    {
        if (!isset($params[self::OPTION_ID])) {
            throw new \InvalidArgumentException(
                'Cannot generate customizable entered option value uid, because option id is missing'
            );
        }

        $optionDetails = [
            self::OPTION_TYPE,
            $params[self::OPTION_ID]
        ];

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return \base64_encode(\implode('/', $optionDetails));
    }
}
