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

namespace Magento\ProductVariantDataExporter\Model\Provider\ProductVariants;

use Magento\ProductVariantDataExporter\Model\Provider\ConfigurableOptionValueUid;

/**
 * Create configurable product variant id
 */
class ConfigurableId implements IdInterface
{
    /**
     * Product variant configurable id child sku key.
     */
    public const CHILD_SKU_KEY = 'childSku';

    /**
     * Product variant configurable id parent sku key.
     */
    public const PARENT_SKU_KEY = 'parentSku';

    /**
     * Returns uid based on parent and child product skus
     *
     * @param string[] $params
     * @return string
     * @throws \InvalidArgumentException
     */
    public function resolve(array $params): string
    {
        if (!isset($params[self::CHILD_SKU_KEY], $params[self::PARENT_SKU_KEY])) {
            throw new \InvalidArgumentException(
                'Cannot generate configurable id, because parent or child sku is missing'
            );
        }

        $uid = [
            ConfigurableOptionValueUid::OPTION_TYPE,
            $params[self::PARENT_SKU_KEY],
            $params[self::CHILD_SKU_KEY]
        ];

        return \hash('md5', implode('/', $uid));
    }
}
