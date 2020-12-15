<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
