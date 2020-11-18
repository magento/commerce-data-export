<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Plugin;

class ConfigUpdateExport
{
    /**
     * @var \Magento\ConfigurationDataExporter\Api\ConfigRegistryInterface
     */
    private $configRegistry;
    private \Magento\ConfigurationDataExporter\Model\ConfigExportCallbackInterface $configExportCallback;

    /**
     * ConfigUpdateExport constructor.
     *
     * @param \Magento\ConfigurationDataExporter\Api\ConfigRegistryInterface $configRegistry
     * @param \Magento\ConfigurationDataExporter\Model\ConfigExportCallbackInterface $configExportCallback
     */
    public function __construct(
        \Magento\ConfigurationDataExporter\Api\ConfigRegistryInterface $configRegistry,
        \Magento\ConfigurationDataExporter\Model\ConfigExportCallbackInterface $configExportCallback
    ) {
        $this->configRegistry = $configRegistry;
        $this->configExportCallback = $configExportCallback;
    }

    /**
     * Trigger configuration publish.
     *
     * @param \Magento\Config\Model\Config $subject
     * @param $result
     * @return mixed
     */
    public function afterSave(\Magento\Config\Model\Config $subject, $result)
    {
        if (!$this->configRegistry->isEmpty()) {
            $this->configExportCallback->execute(
                \Magento\ConfigurationDataExporter\Model\ConfigExportCallback::EVENT_TYPE_UPDATE,
                $this->configRegistry->getValues()
            );
        }

        return $result;
    }
}
