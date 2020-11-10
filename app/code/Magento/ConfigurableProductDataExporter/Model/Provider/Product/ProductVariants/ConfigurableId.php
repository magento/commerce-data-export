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
     * Product variant configurable id child key.
     */
    public const CHILD_ID_KEY = 'childId';

    /**
     * Product variant configurable id parent key.
     */
    public const PARENT_ID_KEY = 'parentId';

    /**
     * Returns uid based on parent and child product ids
     *
     * @param string[] $params
     * @return string
     * @throws \InvalidArgumentException
     */
    public function resolve(array $params): string
    {
        if (!isset($params[self::CHILD_ID_KEY], $params[self::PARENT_ID_KEY])) {
            throw new \InvalidArgumentException(
                'Cannot generate configurable id, because parent or child id is missing'
            );
        }

        $uid = [
            ConfigurableOptionValueUid::OPTION_TYPE,
            $params[self::PARENT_ID_KEY],
            $params[self::CHILD_ID_KEY]
        ];

        return implode('/', $uid);
    }
}
