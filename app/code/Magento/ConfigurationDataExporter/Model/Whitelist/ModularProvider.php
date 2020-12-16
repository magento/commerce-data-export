<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Model\Whitelist;

/**
 * Whitelist defined in module(s)
 */
class ModularProvider implements \Magento\ConfigurationDataExporter\Api\WhitelistProviderInterface
{
    /**
     * @var array
     */
    private $whitelist;

    /**
     * @param array $whitelist
     */
    public function __construct(array $whitelist = [])
    {
        $this->whitelist = $whitelist;
    }

    /**
     * @inheritDoc
     */
    public function getWhitelist(): array
    {
        return $this->whitelist;
    }
}
