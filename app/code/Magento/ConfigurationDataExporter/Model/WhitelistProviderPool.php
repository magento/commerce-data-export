<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Model;

use Magento\ConfigurationDataExporter\Api\WhitelistProviderInterface;

/**
 * Pool of whitelist providers
 */
class WhitelistProviderPool implements WhitelistProviderInterface
{
    /**
     * @var array
     */
    private $providers;

    /**
     * @param WhitelistProviderInterface[] $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * @inheritDoc
     */
    public function getWhitelist(): array
    {
        $whitelist = [];

        foreach ($this->providers as $provider) {
            if ($provider instanceof WhitelistProviderInterface) {
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                $whitelist = array_merge($whitelist, $provider->getWhitelist());
            }
        }

        return $whitelist;
    }
}
