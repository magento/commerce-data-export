<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product\ProductVariants;

use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\IdInterface;
use Magento\ConfigurableProductDataExporter\Model\Provider\Product\ConfigurableOptionValueUid;

/**
 * Create configurable product variant id
 */
class ConfigurableId implements IdInterface
{
    /**
     * Returns uid based on parent and child product ids
     *
     * @param string[] $params
     * @return string
     */
    public function resolve(string ...$params): string
    {
        array_unshift($params, ConfigurableOptionValueUid::OPTION_TYPE);
        return implode('/', $params);
    }
}
