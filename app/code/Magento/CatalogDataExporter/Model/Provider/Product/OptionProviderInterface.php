<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

/**
 * Interface OptionProviderInterface
 * @deprecared use \Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\ProductOptionProviderInterface
 */
interface OptionProviderInterface
{
    /**
     * Get option values
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array;
}
