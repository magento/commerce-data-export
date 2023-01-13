<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model;

use DateTime;
use DateTimeInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Console\Cli;
use Magento\SalesOrdersDataExporter\Console\Command\Link;
use Magento\SalesOrdersDataExporter\Model\Indexer\DateTimeRangeOrderProcessor;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class OnDemandOrdersExporter
{
    private CommerceDataExportLoggerInterface $logger;
    private FeedIndexMetadata $metadata;
    private DateTimeRangeOrderProcessor $processor;
    private Link $linkCommand;

    public function __construct(
        CommerceDataExportLoggerInterface $logger,
        FeedIndexMetadata                 $metadata,
        DateTimeRangeOrderProcessor       $processor,
        Link $linkCommand
    ) {
        $this->logger = $logger;
        $this->metadata = $metadata;
        $this->processor = $processor;
        $this->linkCommand = $linkCommand;
    }

    public function export(DateTime $from, DateTime $to, OutputInterface $output = null): void
    {
        $output = $output ?? new NullOutput();
        $this->ensureAssignedUuids($from, $to, $output);
        $this->processor->fullReindex($this->metadata, $from, $to);
    }

    private function ensureAssignedUuids(DateTime $from, DateTime $to, OutputInterface $output): void
    {
        $returnCode = $this->linkCommand->prepareForExport(
            $this->metadata->getBatchSize(),
            $output,
            $from->format(DateTimeInterface::W3C),
            $to->format(DateTimeInterface::W3C)
        );
        if ($returnCode != Cli::RETURN_SUCCESS) {
            $this->logger->error('Command "commerce-data-export:orders:link" failed.');
        }
    }
}
