<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Index;

use Magento\Config\Model\Config;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Service\IndexInvalidationManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

/**
 * Invalidates indexes on configuration change.
 *
 * Accepts a configPathToEvent map: each key is a config path (section/group/field),
 * and its value is the invalidation event to fire when that path changes.
 * Multiple paths may map to the same event; each unique event fires at most once per save.
 */
class InvalidateOnConfigChange
{
    private IndexInvalidationManager $invalidationManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CommerceDataExportLoggerInterface $logger
     * @param array<string,string> $configPathToEvent Map of config path => invalidation event name
     * @param IndexInvalidationManager|null $indexInvalidationManager
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CommerceDataExportLoggerInterface $logger,
        private readonly array $configPathToEvent = [],
        ?IndexInvalidationManager $indexInvalidationManager = null
    ) {
        $this->invalidationManager = $indexInvalidationManager
            ?? ObjectManager::getInstance()->get(IndexInvalidationManager::class);
    }

    /**
     * Invalidate indexers for any watched config paths that changed (around plugin).
     *
     * @param Config $subject
     * @param callable $proceed
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(Config $subject, callable $proceed)
    {
        $beforeValues = [];
        $savedSection = $subject->getSection();
        try {
            foreach ($this->configPathToEvent as $path => $event) {
                [$section, $group, $field] = explode('/', (string) $path);
                if ($savedSection === $section && isset($subject['groups'][$group]['fields'][$field])) {
                    $beforeValues[$path] = $this->getConfigValue($path, $subject);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'CDE03-14 Failed to read config values. Indexer invalidation skipped. Error: %s',
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
        }

        $result = $proceed();

        try {
            $eventsToFire = [];
            foreach ($beforeValues as $path => $beforeValue) {
                if ($beforeValue != $this->getConfigValue($path, $subject)) {
                    $eventsToFire[$this->configPathToEvent[$path]] = true;
                }
            }

            foreach (array_keys($eventsToFire) as $event) {
                $this->invalidationManager->invalidate($event);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'CDE03-27 Failed to invalidate indexers after config "%s" change. Error: %s',
                    $savedSection,
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
        }

        return $result;
    }

    /**
     * @param string $path
     * @param Config $config
     * @return mixed
     */
    public function getConfigValue(string $path, Config $config): mixed
    {
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeCode = null;
        if (!empty($config->getWebsite())) {
            $scopeType = ScopeInterface::SCOPE_WEBSITES;
            $scopeCode = $config->getWebsite();
        } elseif (!empty($config->getStore())) {
            $scopeType = ScopeInterface::SCOPE_STORES;
            $scopeCode = $config->getStore();
        }
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }
}
