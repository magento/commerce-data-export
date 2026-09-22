<?php
/**
 * Copyright 2026 Adobe
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

namespace AdobeCommerce\IndexerStatusManager\Console\Command;

use Magento\Framework\Console\Cli;
use AdobeCommerce\IndexerStatusManager\Model\Config;
use AdobeCommerce\IndexerStatusManager\Model\Indexer\StatusManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Re-enables an indexer that was disabled via configuration.
 */
class IndexerEnable extends Command
{
    private const COMMAND_NAME = 'indexer:enable';
    private const ARG_INDEXER = 'indexer';

    /**
     * @param StatusManager $statusManager
     * @param Config $config
     */
    public function __construct(
        private readonly StatusManager $statusManager,
        private readonly Config $config
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Re-enables the given indexer that was disabled via configuration.');
        $this->addArgument(
            self::ARG_INDEXER,
            InputArgument::REQUIRED,
            'Indexer code, e.g. catalog_category_product'
        );
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexerCode = (string)$input->getArgument(self::ARG_INDEXER);
        if (!$this->config->isManageable($indexerCode)) {
            $output->writeln(sprintf(
                '<error>Indexer "%s" is not managed by this command.</error>',
                $indexerCode
            ));
            $output->writeln(sprintf(
                '<comment>Only these indexers are managed here: %s</comment>',
                implode(', ', $this->config->getManageableIndexers()) ?: '(none configured)'
            ));

            return Cli::RETURN_FAILURE;
        }

        $this->statusManager->enable($indexerCode);
        $output->writeln(sprintf('<info>Indexer "%s" has been enabled.</info>', $indexerCode));
        $output->writeln('<comment>Run bin/magento cache:clean config to apply the change.</comment>');

        return Cli::RETURN_SUCCESS;
    }
}
