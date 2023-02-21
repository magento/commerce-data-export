<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Console\Command;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Console\Cli;
use Magento\SalesOrdersDataExporter\Model\OrderUuidManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command provides possibility to full export system configuration
 */
class Link extends Command
{
    private const COMMAND_NAME = 'commerce-data-export:orders:link';
    private const OPTION_STATE = 'state';
    private const OPTION_FROM = 'from';
    private const OPTION_TO = 'to';
    private const OPTION_BATCH_SIZE = 'batch-size';

    /**
     * @var OrderUuidManager
     */
    private $orderUuidManager;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private $logger;

    /**
     * @param OrderUuidManager $orderUuidManager
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        OrderUuidManager $orderUuidManager,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orderUuidManager = $orderUuidManager;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->addOption(
            self::OPTION_STATE,
            's',
            InputOption::VALUE_OPTIONAL,
            'Statuses for filter order',
            ""
        );
        $this->addOption(
            self::OPTION_FROM,
            'f',
            InputOption::VALUE_OPTIONAL,
            'Date from for filter order',
            ""
        );
        $this->addOption(
            self::OPTION_TO,
            't',
            InputOption::VALUE_OPTIONAL,
            'Date to for filter order',
            ""
        );
        $this->addOption(
            self::OPTION_BATCH_SIZE,
            'b',
            InputOption::VALUE_OPTIONAL,
            'Batch size',
            10000
        );
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Assign UUID to orders to allow further export via "saas:resync" command');

        parent::configure();
    }

    /**
     * Updating UUID
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $state = $input->getOption(self::OPTION_STATE);
            $batchSize = (int) $input->getOption(self::OPTION_BATCH_SIZE);
            $from = $input->getOption(self::OPTION_FROM)
                ? (new \DateTime($input->getOption(self::OPTION_FROM)))->format(\DateTimeInterface::W3C)
                : null;
            $to = $input->getOption(self::OPTION_TO)
                ? (new \DateTime($input->getOption(self::OPTION_TO)))->format(\DateTimeInterface::W3C)
                : null;

            $output->writeln(
                sprintf(
                    '<info>Start updating UUID with parameters [state=%s, from=%s, to=%s, batch_size=%s]</info>',
                    $state ?: 'all',
                    $from ?: date(\DateTimeInterface::W3C, 0),
                    $to ?: date(\DateTimeInterface::W3C),
                    $batchSize
                )
            );

            $updatedEntities = $this->assignUuidsToOrderEntities($batchSize, $from, $to, $state);

            $output->writeln("<info>Assign UUID completed successfully, $updatedEntities entities updated</info>");

            return CLI::RETURN_SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to update UUID. Check logs</error>');
            $this->logger->error(
                sprintf('Command "%s" failed. Error message: %s', self::COMMAND_NAME, $e->getMessage())
            );

            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * Updating UUID
     *
     * @param int $batchSize
     * @param string|null $from
     * @param string|null $to
     * @param string $state
     * @return int
     */
    public function assignUuidsToOrderEntities(
        int $batchSize,
        string $from = null,
        string $to = null,
        string $state = ''
    ): int {
        return $this->orderUuidManager->assignByDate($batchSize, $from, $to, $state);
    }
}
