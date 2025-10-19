<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions;

/**
 * Interface ProductOptionProviderInterface
 */
interface ProductOptionProviderInterface
{
    /**
     * Get option with option values
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array;
}
