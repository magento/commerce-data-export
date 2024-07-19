<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\ConfigurationDataExporter\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Full system configuration sync on every setup:upgrade to apply changes from deploy config and config.xml
 */
class RecurringData implements \Magento\Framework\Setup\InstallDataInterface
{
    /**
     * @var \Magento\ConfigurationDataExporter\Model\FullExportProcessorInterface
     */
    private $exportProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\ConfigurationDataExporter\Model\FullExportProcessorInterface $exportProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\ConfigurationDataExporter\Model\FullExportProcessorInterface $exportProcessor,
        LoggerInterface $logger
    ) {
        $this->exportProcessor = $exportProcessor;
        $this->logger = $logger;
    }

    /**
     * Export configuration on every setup:upgrade run.
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        try {
            $this->exportProcessor->process();
        } catch (\Throwable $e) {
            $this->logger->error('Full configuration sync failed.');
        }
    }
}
