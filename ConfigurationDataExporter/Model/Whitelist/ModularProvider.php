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
