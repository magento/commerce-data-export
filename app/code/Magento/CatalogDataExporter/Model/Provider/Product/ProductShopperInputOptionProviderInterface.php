<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

/**
 * Product shopper input options data provider.
 */
interface ProductShopperInputOptionProviderInterface
{
    /**
     * Get product shopper input option with option values
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array;
}
