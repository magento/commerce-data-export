<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Observer for order feed indexation during save operation
 */
class ReindexOrderFeed implements ObserverInterface
{
    /**
     * Review feed indexer id
     */
    public const ORDER_FEED_INDEXER = 'sales_order_data_exporter_v2';

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Reindex orders if indexer has "on save" mode
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Framework\Indexer\IndexerInterface $indexer */
        $indexer = $this->indexerRegistry->get(self::ORDER_FEED_INDEXER);

        if (!$indexer->isScheduled()) {
            $event = $observer->getEvent();
            /** @var Order $order */
            $order = $event->getOrder();
            $indexer->reindexRow($order->getId());
        }
    }
}
