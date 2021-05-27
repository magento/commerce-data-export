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

class ConfigUpdateExport
{
    /**
     * @var ConfigRegistryInterface
     */
    private $configRegistry;

    /**
     * @var ConfigExportCallbackInterface
     */
    private $configExportCallback;

    /**
     * @param ConfigRegistryInterface $configRegistry
     * @param ConfigExportCallbackInterface $configExportCallback
     */
    public function __construct(
        ConfigRegistryInterface $configRegistry,
        ConfigExportCallbackInterface $configExportCallback
    ) {
        $this->configRegistry = $configRegistry;
        $this->configExportCallback = $configExportCallback;
    }

    /**
     * Trigger configuration publish.
     *
     * @param Config $subject
     * @param Config $result
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Config $subject, Config $result): Config
    {
        if (!$this->configRegistry->isEmpty()) {
            $this->configExportCallback->execute(
                ConfigExportCallbackInterface::EVENT_TYPE_UPDATE,
                $this->configRegistry->getValues()
            );
        }

        return $result;
    }
}
