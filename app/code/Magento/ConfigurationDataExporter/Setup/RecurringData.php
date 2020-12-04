<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

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
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\ConfigurationDataExporter\Model\FullExportProcessorInterface $exportProcessor
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\ConfigurationDataExporter\Model\FullExportProcessorInterface $exportProcessor,
        \Psr\Log\LoggerInterface $logger
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
