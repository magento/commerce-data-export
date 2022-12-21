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
use Magento\SalesOrdersDataExporter\Console\Command\Link;
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
    private const COMMAND_NAME = 'commerce-data-export:orders:export-on-demmand';
    private CommerceDataExportLoggerInterface $logger;
    private FeedIndexMetadata $metadata;
    private DateTimeRangeOrderProcessor $processor;
    private DateTimeFactory $dateTimeFactory;
    private Link $linkCommand;

    public function __construct(
        CommerceDataExportLoggerInterface $logger,
        FeedIndexMetadata                 $metadata,
        DateTimeRangeOrderProcessor       $processor,
        DateTimeFactory                   $dateTimeFactory,
        Link                              $link
    )
    {
        $this->logger = $logger;
        $this->metadata = $metadata;
        $this->processor = $processor;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->linkCommand = $link;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Exports orders since certain time in the past on demmand.')
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

        return Cli::RETURN_SUCCESS;
    }

    private function ensureAssignedUuids(DateTime $from, DateTime $to, OutputInterface $output): int
    {


        try {
            return $this->linkCommand->
            prepareToExport(10000, $output, $from->format(DateTimeInterface::W3C), $to->format(DateTimeInterface::W3C));
        } catch (ExceptionInterface $e) {
            $this->logger->error(
                sprintf('Command "commerce-data-export:orders:link" failed. Message: %s', $e->getMessage())
            );

            return Cli::RETURN_FAILURE;
        }
    }
}
