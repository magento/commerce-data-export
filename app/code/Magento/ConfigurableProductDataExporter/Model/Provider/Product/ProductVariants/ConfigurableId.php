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
 * Create configurable product variant id in base64 encode
 */
class ConfigurableId implements IdInterface
{
    /**
     * Returns uid based on parent and child product ids
     *
     * @param string $parentId
     * @param string $childId
     * @return string
     */
    public function resolve(string $parentId, string $childId): string
    {
        $uid = [
            ConfigurableOptionValueUid::OPTION_TYPE,
            $parentId,
            $childId
        ];

        return implode('/', $uid);
    }
}
