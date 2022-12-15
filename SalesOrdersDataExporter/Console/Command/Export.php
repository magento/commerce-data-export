<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Console\Command;

use DateTime;
use DateTimeInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\SalesOrdersDataExporter\Model\Indexer\DateTimeRangeOrderProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
    )
    {
        $this->logger = $logger;
        $this->metadata = $metadata;
        $this->processor = $processor;
        parent::__construct();
        $this->dateTimeFactory = $dateTimeFactory;
    }

    protected function configure()
    {
        $this
            ->setName('commerce-data-export:orders:export')
            ->setDescription('Exports orders since certain time in the past.')
            ->addArgument(
                'from',
                InputArgument::REQUIRED,
                'From date time'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = $this->dateTimeFactory->create($input->getArgument('from'));
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
        $command = $this->getApplication()->find('commerce-data-export:orders:link');
        $input = new ArrayInput([
            '-f' => $from->format(DateTimeInterface::W3C),
            '-t' => $to->format(DateTimeInterface::W3C)
        ]);

        try {
            return $command->run($input, $output);
        } catch (ExceptionInterface $e) {
            $this->logger->error(
                sprintf('Command "commerce-data-export:orders:link" failed. Message: %s', $e->getMessage())
            );

            return Cli::RETURN_FAILURE;
        }
    }
}
