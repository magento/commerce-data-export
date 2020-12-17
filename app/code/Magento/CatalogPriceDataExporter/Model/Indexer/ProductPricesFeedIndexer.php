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
        foreach ($this->eventPool->getFullReindexResolvers() as $resolver) {
            foreach ($resolver->retrieve() as $eventData) {
                $this->processEvents($eventData);
            }
        }
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
        $indexData = $this->prepareIndexData($ids);
        foreach ($indexData as $priceType => $data) {
            $eventResolver = $this->eventPool->getPartialReindexResolver($priceType);
            foreach ($eventResolver->retrieve($data) as $eventData) {
                $this->processEvents($eventData);
            }
        }
    }

    /**
     * Process event data
     *
     * @param array $eventData
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processEvents(array $eventData): void
    {
        $events = $this->eventBuilder->build($eventData);
        $this->publisher->publish('export.product.prices', \json_encode($events));
        $this->logger->info(\json_encode($events));
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
                continue;
            }
            $output[$data['price_type']][] = $data;
        }
        return $output;
    }
}
