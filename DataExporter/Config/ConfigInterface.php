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

namespace Magento\DataExporter\Config;

/**
 * Interface ConfigInterface
 */
interface ConfigInterface
{
    /**
     * Get config value
     *
     * @param string $profileName
     * @return array
     */
    public function get(string $profileName) : array;

    /**
     * Check if type is scalar
     *
     * @param string $typeName
     * @return bool
     */
    public function isScalar(string $typeName) : bool;
}
