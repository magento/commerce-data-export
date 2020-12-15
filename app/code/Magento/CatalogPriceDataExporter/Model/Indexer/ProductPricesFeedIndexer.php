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
        $indexData = [
            'product_price' => [
            ],
//            'tier_price',
//            'custom_option',
//            'custom_option_price',
//            'custom_option_type',
//            'custom_option_type_price',
//            'downloadable_link',
//            'downloadable_link_price',
//            'bundle_variation',
//            'configurable_variation'
        ];

        $this->process($indexData);
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
        $this->process($indexData);
    }

    /**
     * @param array $indexData
     * @param bool $fullSync
     * @throws \Magento\DataExporter\Exception\UnableRetrieveData
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function process($indexData)
    {
        foreach ($indexData as $priceType => $data) {
            $eventResolver = $this->eventPool->getEventResolver($priceType);
            foreach ($eventResolver->retrieve($data) as $oneIteration) {
                $events = $this->eventBuilder->build($oneIteration);
                $this->publisher->publish('export.product.prices', \json_encode($events));
                $this->logger->info(\json_encode($events));
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
                continue;
            }

            $output[$data['price_type']][] = $data;
        }

        return $output;
    }
}
