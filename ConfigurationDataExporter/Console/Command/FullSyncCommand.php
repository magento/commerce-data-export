<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurationDataExporter\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command provides possibility to full export system configuration
 */
class FullSyncCommand extends \Symfony\Component\Console\Command\Command
{
    const COMMAND_NAME = 'commerce-data-export:config:export';
    const INPUT_OPTION_STORE_ID = 'store';

    /**
     * @var \Magento\ConfigurationDataExporter\Model\FullExportProcessorInterface
     */
    private $exportProcessor;

    /**
     * @param \Magento\ConfigurationDataExporter\Model\FullExportProcessorInterface $exportProcessor
     */
    public function __construct(
        \Magento\ConfigurationDataExporter\Model\FullExportProcessorInterface $exportProcessor
    ) {
        $this->exportProcessor = $exportProcessor;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->addOption(
            self::INPUT_OPTION_STORE_ID,
            null,
            InputOption::VALUE_OPTIONAL,
            'Store ID for export configuration',
            'All'
        );
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Export full system configuration data to queue');

        parent::configure();
    }

    /**
     * Execute full configuration export from magento to queue.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $input->getOption(self::INPUT_OPTION_STORE_ID);

        $output->writeln(
            sprintf(
                '<info>Exporting configuration for %s store(s)</info>',
                $storeId
            )
        );

        $storeId = is_numeric($storeId) ? (int)$storeId : null;

        try {
            $this->exportProcessor->process($storeId);
        } catch (\Throwable $e) {
            $output->writeln(
                sprintf(
                    '<error>Exporting configuration failed: %s</error>',
                    $e->getMessage()
                )
            );

            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
