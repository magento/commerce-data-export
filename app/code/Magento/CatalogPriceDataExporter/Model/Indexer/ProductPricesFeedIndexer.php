<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Indexer;

use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\EventPool;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for indexing product prices and provide price change events
 */
class ProductPricesFeedIndexer implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var EventPool
     */
    private $eventPool;

    /**
     * @var EventBuilder
     */
    private $eventBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param EventPool $eventPool
     * @param EventBuilder $eventBuilder
     * @param LoggerInterface $logger
     * @param PublisherInterface $publisher
     */
    public function __construct(
        EventPool $eventPool,
        EventBuilder $eventBuilder,
        LoggerInterface $logger,
        PublisherInterface $publisher
    ) {
        $this->eventPool = $eventPool;
        $this->eventBuilder = $eventBuilder;
        $this->logger = $logger;
        $this->publisher = $publisher;
    }

    /**
     * @inheritdoc
     */
    public function executeFull(): void
    {
        // TODO: Implement executeFull() method.
    }

    /**
     * @inheritdoc
     */
    public function executeList(array $ids): void
    {
        // TODO: Implement executeList() method.
    }

    /**
     * @inheritdoc
     */
    public function executeRow($id): void
    {
        // TODO: Implement executeRow() method.
    }

    /**
     * @inheritdoc
     */
    public function execute($ids): void
    {
        $events = [];

        $indexData = $this->prepareIndexData($ids);

        foreach ($indexData as $priceType => $data) {
            $events[] = $this->eventPool->getEventResolver($priceType)->retrieve($data);
        }

        $events = !empty($events) ? \array_merge_recursive(...$events) : [];

        if (!empty($events)) {
            $events = $this->eventBuilder->build($events);
            $this->logger->info('Product price events.', ['events' => $events]);

            //todo: add a callback
            foreach ($events as $eventData) {
                $this->publisher->publish('export.product.prices', \json_encode($eventData));
            }
        }
    }

    /**
     * Prepare index data
     *
     * @param array $indexData
     *
     * @return array
     */
    private function prepareIndexData(array $indexData): array
    {
        $output = [];
        foreach ($indexData as $data) {
            if (!\is_array($data)) {
                continue; // TODO throw exception / log error
            }

            $output[$data['price_type']][] = $data;
        }

        return $output;
    }
}
