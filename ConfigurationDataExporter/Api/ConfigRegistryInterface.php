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

namespace Magento\ConfigurationDataExporter\Api;

/**
 * Accumulate changed config values for export
 */
interface ConfigRegistryInterface
{
    /**
     * Get array of config values for export.
     *
     * @return array
     */
    public function getValues(): array;

    /**
     * Add config value to registry.
     *
     * @param array $value
     * @return void
     */
    public function addValue(array $value = []): void;

    /**
     * Check if registry empty..
     *
     * @return bool
     */
    public function isEmpty(): bool;
}
