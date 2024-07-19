<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\SalesOrdersDataExporter\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Observer for order feed indexation during void operation
 */
class ReindexOrderFeedOnVoid implements ObserverInterface
{
    /**
     * Sales order feed indexer id
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
     * Reindex orders if indexer has "on save" mode.
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
            /** @var Payment $payment */
            $payment =  $event->getPayment();
            $indexer->reindexRow($payment->getParentId());
        }
    }
}
