<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
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
