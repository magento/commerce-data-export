<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions;

/**
 * Option value uid provider interface
 */
interface OptionValueUidInterface
{
    /**
     * Get option value uid
     *
     * @param string[] $params
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function resolve(array $params) : string;
}
