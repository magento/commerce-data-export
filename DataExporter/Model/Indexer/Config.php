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

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Feed indexer config.
 */
class Config
{
    /**
     * Pass option --threadCountOverride to modify number of active threads for feed indexing and sync,
     * for example: bin/magento saas:resync --feed=products --thread-count=20
     */
    private const EXPORTER_THREAD_COUNT = 'thread-count';

    /**
     * Pass option --batchSize to modify the batch size for feed indexing and sync,
     * for example: bin/magento saas:resync --feed=products --batch-size=500
     */
    private const EXPORTER_BATCH_SIZE = 'batch-size';

    /**
     * Pass option --continue-resync to continue `saas:resync` process from the last position,
     * for example: bin/magento saas:resync --feed=products --continue-resync
     *
     * Resynchronization will be started with the next item that follows the last exported item in ascending order.
     * Warning: previously exported items will not be exported with these settings even if their state has been changed.
     *
     * This option is useful for exporting a large amount of products. For example,
     * assume you have 10M products in your system,
     * but only 5M have been exported. With this option, you can start exporting the next 5M immediately.
     */
    private const EXPORTER_CONTINUE_RESYNC = 'continue-resync';

    private const EXPORTER_CLEAN_UP_FEED = 'cleanup-feed';

    private const EXPORTER_DRY_RUN = 'dry-run';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var int
     */
    private int $defaultBatchSize;

    /**
     * @var int
     */
    private int $defaultThreadCount;

    /**
     * @var ConfigOptionsHandler
     */
    private ConfigOptionsHandler $configOptionsHandler;
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigOptionsHandler $configOptionsHandler
     * @param int $defaultBatchSize
     * @param int $defaultThreadCount
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigOptionsHandler $configOptionsHandler,
        int $defaultBatchSize = 100,
        int $defaultThreadCount = 1
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configOptionsHandler = $configOptionsHandler;
        $this->defaultBatchSize = $defaultBatchSize;
        $this->defaultThreadCount = $defaultThreadCount;
    }

    /**
     * Batch size for feed indexing and sync.
     *
     * @param string $feedName
     * @return int
     */
    public function getBatchSize(string $feedName): int
    {
        $configPath = sprintf('commerce_data_export/feeds/%s/batch_size', $feedName);
        $configOptions = $this->configOptionsHandler->getConfigOptionsPool();
        $batchSize = $configOptions->getFeedOption(self::EXPORTER_BATCH_SIZE)
            ?? $this->scopeConfig->getValue($configPath) ?? $this->defaultBatchSize;

        return (int)$batchSize;
    }

    /**
     * Thread count for feed indexing and sync.
     *
     * @param string $feedName
     * @return int
     */
    public function getThreadCount(string $feedName): int
    {
        $configPath = sprintf('commerce_data_export/feeds/%s/thread_count', $feedName);
        $configOptions = $this->configOptionsHandler->getConfigOptionsPool();
        $threadCount = $configOptions->getFeedOption(self::EXPORTER_THREAD_COUNT) ??
            $this->scopeConfig->getValue($configPath) ?? $this->defaultThreadCount;

        return (int)$threadCount;
    }

    /**
     * Whether resync  process should be continued from the last position
     *
     * @return bool
     */
    public function isResyncShouldBeContinued(): bool
    {
        $configOptions = $this->configOptionsHandler->getConfigOptionsPool();
        return $configOptions->getFeedOption(self::EXPORTER_CONTINUE_RESYNC) ?? false;
    }

    /**
     * Check if feed table should be cleaned up before export
     *
     * @return bool
     */
    public function isCleanUpFeed(): bool
    {
        $configOptions = $this->configOptionsHandler->getConfigOptionsPool();
        return $configOptions->getFeedOption(self::EXPORTER_CLEAN_UP_FEED) ?? false;
    }

    /**
     * Check if dry run mode is enabled
     *
     * @return bool
     */
    public function isDryRun(): bool
    {
        $configOptions = $this->configOptionsHandler->getConfigOptionsPool();
        return $configOptions->getFeedOption(self::EXPORTER_DRY_RUN) ?? false;
    }

    /**
     * Check if submitted items should be included in dry run mode
     *
     * @return bool
     */
    public function includeSubmittedInDryRun(): bool
    {
        $configOptions = $this->configOptionsHandler->getConfigOptionsPool();
        return $this->isDryRun() && $configOptions->getFeedOption(self::EXPORTER_CLEAN_UP_FEED) ?? false;
    }
}
