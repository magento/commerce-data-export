<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Console\Command;

use DateTime;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\SalesOrdersDataExporter\Model\Indexer\DateTimeRangeOrderProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command export orders since certain time in the past
 */
class Export extends Command
{
    private CommerceDataExportLoggerInterface $logger;
    private FeedIndexMetadata $metadata;
    private DateTimeRangeOrderProcessor $processor;
    private DateTimeFactory $dateTimeFactory;

    public function __construct(
        CommerceDataExportLoggerInterface $logger,
        FeedIndexMetadata                 $metadata,
        DateTimeRangeOrderProcessor       $processor,
        DateTimeFactory                   $dateTimeFactory
    ) {
        $this->logger = $logger;
        $this->metadata = $metadata;
        $this->processor = $processor;
        parent::__construct();
        $this->dateTimeFactory = $dateTimeFactory;
    }

    protected function configure()
    {
        $this->addOption(
            'from',
            'f',
            InputOption::VALUE_REQUIRED,
            'From timestamp'
        );

        $this->setName('commerce-data-export:orders:export')
            ->setDescription('Exports orders since certain time in the past.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = $this->dateTimeFactory->create($input->getOption('from'));
        $to = $this->dateTimeFactory->create();

        $returnCode = $this->ensureAssignedUuids($from, $to, $output);
        if ($returnCode != 0) {
            return Cli::RETURN_FAILURE;
        }

        $this->processor->fullReindex($this->metadata, $from, $to);

        return 0;
    }

    private function ensureAssignedUuids(DateTime $from, DateTime $to, OutputInterface $output): int
    {
        try {
            // TODO: extract command logic into class to avoid calling the command
            $command = $this->getApplication()->find('commerce-data-export:orders:link');
            return $command->run(new ArrayInput(['-f' => $from, '-t' => $to]), $output);
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf('Command "commerce-data-export:orders:link" failed. Error message: %s', $e->getMessage())
            );

            return Cli::RETURN_FAILURE;
        }
    }
}
