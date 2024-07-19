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
 * Customizable selected option value uid generator
 */
class CustomizableSelectedOptionValueUid implements OptionValueUidInterface
{
    /**
     * Customizable option type name
     */
    private const OPTION_TYPE = 'custom-option';

    /**
     * Option id key
     */
    public const OPTION_ID = 'optionId';

    /**
     * Option value id key
     */
    public const OPTION_VALUE_ID = 'optionValueId';

    /**
     * Returns uid based on option id and option value id
     *
     * @param string[] $params
     *
     * @return string
     */
    public function resolve(array $params): string
    {
        if (!isset($params[self::OPTION_ID], $params[self::OPTION_VALUE_ID])) {
            throw new \InvalidArgumentException(
                'Cannot generate customizable selectable option value uid,
                because option id or option value id is missing'
            );
        }

        $optionDetails = [
            self::OPTION_TYPE,
            $params[self::OPTION_ID],
            $params[self::OPTION_VALUE_ID]
        ];

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return \base64_encode(\implode('/', $optionDetails));
    }
}
