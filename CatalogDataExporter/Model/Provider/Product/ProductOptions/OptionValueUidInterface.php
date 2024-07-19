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
