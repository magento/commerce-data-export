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
