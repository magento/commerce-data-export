<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
