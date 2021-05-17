<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Api;

/**
 * Provider of allowed for export configuration paths
 */
interface WhitelistProviderInterface
{
    /**
     * Get whitelisted configuration paths to filter export.
     *
     * @return array
     */
    public function getWhitelist(): array;
}
