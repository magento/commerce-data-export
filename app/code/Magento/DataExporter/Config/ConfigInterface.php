<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
