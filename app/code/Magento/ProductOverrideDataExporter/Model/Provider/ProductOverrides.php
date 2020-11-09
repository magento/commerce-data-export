<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductOverride\Model\Provider;

/**
 * Class ProductOverrides
 * @package Magento\ProductOverride\Model\Provider\ProductOverrides
 */
class ProductOverrides
{
    /**
     * @param array $values
     * @return \int[][]
     */
    public function get(array $values) : array
    {
        return [['productId' => 11]];
    }
}
