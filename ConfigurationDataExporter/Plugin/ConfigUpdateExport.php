<?php
/**
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
