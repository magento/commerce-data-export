<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Indexer;

use Magento\DataExporter\Model\Indexer\DataSerializerInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Indexer\FeedIndexProcessorInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\BatchIteratorFactory;

class DateTimeRangeOrderProcessor
{
    private ResourceConnection $resourceConnection;
    private BatchIteratorFactory $batchIteratorFactory;
    private DataSerializerInterface $serializer;
    private FeedIndexProcessorInterface $delegatingProcessor;

    public function __construct(
        ResourceConnection $resourceConnection,
        BatchIteratorFactory $batchIteratorFactory,
        DataSerializerInterface $serializer,
        FeedIndexProcessorInterface $delegatingProcessor
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->batchIteratorFactory = $batchIteratorFactory;
        $this->serializer = $serializer;
        $this->delegatingProcessor = $delegatingProcessor;
    }

    public function fullReindex(FeedIndexMetadata $metadata, $from, $to): void
    {
        $idsProvider = new DateTimeRangeOrderIdsProvider(
            $this->resourceConnection,
            $this->batchIteratorFactory,
            $from,
            $to
        );
        $this->delegatingProcessor->fullReindex($metadata, $this->serializer, $idsProvider);
    }
}
