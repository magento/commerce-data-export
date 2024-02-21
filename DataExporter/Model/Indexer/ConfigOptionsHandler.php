<?php
/*************************************************************************
 *
 * Copyright 2023 Adobe
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
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Handler responsible for the ConfigOptionsPool initialization and storage
 */
class ConfigOptionsHandler
{
    private ConfigOptionsPoolFactory $configOptionsPoolFactory;

    private ?ConfigOptionsPool $configOptionsPool = null;

    /**
     * @param array $feedOptions
     */
    public function __construct(ConfigOptionsPoolFactory $configOptionsPoolFactory)
    {
        $this->configOptionsPoolFactory = $configOptionsPoolFactory;
    }

    /**
     * Initialize ConfigOptionsPool with given options
     *
     * @param array $options
     * @return void
     */
    public function initialize(array $options = []): void
    {
        if ($this->configOptionsPool) {
            throw new \RuntimeException(ConfigOptionsPool::class . ' has been initialized');
        }
        $this->configOptionsPool = $this->configOptionsPoolFactory->create(['feedOptions' => $options]);
    }
    /**
     * Retrieve ConfigOptionsPool and if it's not initialized yet, initialize it with empty options list
     *
     * @return ConfigOptionsPool
     */
    public function getConfigOptionsPool(): ConfigOptionsPool
    {
        if (!$this->configOptionsPool) {
            $this->initialize();
        }
        return $this->configOptionsPool;
    }
}
