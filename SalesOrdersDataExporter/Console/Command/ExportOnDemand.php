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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command export orders since certain time in the past
 */
class ExportOnDemand extends Command
{
    private const COMMAND_NAME = 'commerce-data-export:orders:export-on-demand';
    private CommerceDataExportLoggerInterface $logger;
    private FeedIndexMetadata $metadata;
    private DateTimeRangeOrderProcessor $processor;
    private DateTimeFactory $dateTimeFactory;
    private Link $linkCommand;

    /**
     * @param CommerceDataExportLoggerInterface $logger
     * @param FeedIndexMetadata $metadata
     * @param DateTimeRangeOrderProcessor $processor
     * @param DateTimeRangeOrderProcessor dateTimeFactory
     * @param Link $link
     */
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

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Exports orders since certain time in the past on demand.')
            ->addArgument(
                'from',
                InputArgument::REQUIRED,
                'From date time'
            );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = $this->dateTimeFactory->create($input->getArgument('from'));
        $to = $this->dateTimeFactory->create();

        $returnCode = $this->ensureAssignedUuids($from, $to, $output);
        if ($returnCode != Cli::RETURN_SUCCESS) {
            return Cli::RETURN_FAILURE;
        }

        $this->processor->fullReindex($this->metadata, $from, $to);

        return Cli::RETURN_SUCCESS;
    }

    private function ensureAssignedUuids(DateTime $from, DateTime $to, OutputInterface $output): int
    {
        $returnCode = $this->linkCommand->
            prepareForExport(
                $this->metadata->getBatchSize(),
                $output,
                $from->format(DateTimeInterface::W3C),
                $to->format(DateTimeInterface::W3C)
            );
        if ($returnCode != Cli::RETURN_SUCCESS) {
            $this->logger->error('Command "commerce-data-export:orders:link" failed.');
        }
        return $returnCode;
    }
}
