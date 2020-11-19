<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Event\Data;

/**
 * Data object for changed config
 */
class Data
{
    /**
     * @var \Magento\ConfigurationDataExporter\Event\Data\Config[]
     */
    private $config;

    /**
     * @param \Magento\ConfigurationDataExporter\Event\Data\Config[] $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Get config.
     *
     * @return \Magento\ConfigurationDataExporter\Event\Data\Config[]
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set config.
     *
     * @param \Magento\ConfigurationDataExporter\Event\Data\Config[] $config
     *
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
}
