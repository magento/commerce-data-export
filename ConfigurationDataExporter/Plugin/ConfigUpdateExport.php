<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Plugin;

use Magento\Config\Model\Config;
use Magento\ConfigurationDataExporter\Api\ConfigRegistryInterface;
use Magento\ConfigurationDataExporter\Model\ConfigExportCallbackInterface;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;

class ConfigUpdateExport
{
    private ConfigRegistryInterface $configRegistry;
    private ConfigExportCallbackInterface $configExportCallback;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ConfigRegistryInterface $configRegistry
     * @param ConfigExportCallbackInterface $configExportCallback
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ConfigRegistryInterface $configRegistry,
        ConfigExportCallbackInterface $configExportCallback,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->configRegistry = $configRegistry;
        $this->configExportCallback = $configExportCallback;
        $this->logger = $logger;
    }

    /**
     * Trigger configuration publish.
     *
     * @param Config $subject
     * @param mixed $result
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Config $subject, $result)
    {
        try {
            if (!$this->configRegistry->isEmpty()) {
                $this->configExportCallback->execute(
                    ConfigExportCallbackInterface::EVENT_TYPE_UPDATE,
                    $this->configRegistry->getValues()
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
        return $result;
    }
}
