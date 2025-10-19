<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
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
