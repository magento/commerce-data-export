<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
