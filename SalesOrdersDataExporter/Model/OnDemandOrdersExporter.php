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
use Magento\SalesOrdersDataExporter\Console\Command\Link;
use Magento\SalesOrdersDataExporter\Model\Indexer\DateTimeRangeOrderProcessor;

class OnDemandOrdersExporter
{
    private $logger;
    private $metadata;
    private $processor;
    private $linkCommand;

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

    public function export(DateTime $from, DateTime $to): void
    {
        $this->linkCommand->assignUuidsToOrderEntities(
            $this->metadata->getBatchSize(),
            $from->format(DateTimeInterface::W3C),
            $to->format(DateTimeInterface::W3C)
        );
        $this->processor->fullReindex($this->metadata, $from, $to);
        // TODO: would be interesting here to return the amount of orders indexed but impl needs further thinking
    }

}
