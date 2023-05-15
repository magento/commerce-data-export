<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Index;

use Magento\CatalogDataExporter\Model\Indexer\IndexInvalidationManager;
use Magento\Config\Model\Config;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class InvalidateOnConfigChange
 *
 * Invalidates indexes on configuration change
 */
class InvalidateOnConfigChange
{
    private IndexInvalidationManager $invalidationManager;
    private ScopeConfigInterface $scopeConfig;
    private string $invalidationEvent;
    private array $configValues;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param IndexInvalidationManager $invalidationManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CommerceDataExportLoggerInterface $logger
     * @param string $invalidationEvent
     * @param array $configValues
     */
    public function __construct(
        IndexInvalidationManager $invalidationManager,
        ScopeConfigInterface  $scopeConfig,
        CommerceDataExportLoggerInterface $logger,
        string $invalidationEvent = 'config_changed',
        array $configValues = []
    ) {
        $this->invalidationManager = $invalidationManager;
        $this->scopeConfig = $scopeConfig;
        $this->invalidationEvent = $invalidationEvent;
        $this->configValues = $configValues;
        $this->logger = $logger;
    }

    /**
     * Invalidate indexer if relevant config value is changed
     *
     * @param Config $subject
     * @return null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(Config $subject)
    {
        try {
            $savedSection = $subject->getSection();
            foreach ($this->configValues as $searchValue) {
                $path = explode('/', $searchValue);
                $section = $path[0];
                $group = $path[1];
                $field = $path[2];
                if ($savedSection == $section) {
                    if (isset($subject['groups'][$group]['fields'][$field])) {
                        $savedField = $subject['groups'][$group]['fields'][$field];
                        $beforeValue = $this->scopeConfig->getValue($searchValue);
                        $afterValue = $savedField['value'] ?? $savedField['inherit'] ?? null;
                        if ($beforeValue != $afterValue) {
                            $this->invalidationManager->invalidate($this->invalidationEvent);
                            break;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
        return null;
    }
}
