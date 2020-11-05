<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product\ProductVariants;

use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\OptionValueInterface;

/**
 * Create configurable product variant option value uid in base64 encode
 */
class ConfigurableOptionValue implements OptionValueInterface
{
    /**
     * Returns uid based on parent id, option id and optionValue uid
     *
     * @param string $parentId
     * @param string $optionId
     * @param string $optionValueUid
     * @return string
     */
    public function resolve(string $parentId, string $optionId, string $optionValueUid): string
    {
        $uid = \sprintf(
            '%1$s:%2$s/%3$s',
            $parentId,
            $optionId,
            $optionValueUid
        );

        return $uid;
    }
}
