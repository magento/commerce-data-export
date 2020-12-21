<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\PartialReindex;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Indexer\PriceBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\DownloadableLinkPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing downloadable product link price events
 */
class DownloadableLinkPriceEvent implements PartialReindexPriceProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DownloadableLinkPrice
     */
    private $downloadableLinkPrice;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PriceBuilder
     */
    private $priceBuilder;

    /**
     * @var EventKeyGenerator
     */
    private $eventKeyGenerator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param DownloadableLinkPrice $downloadableLinkPrice
     * @param StoreManagerInterface $storeManager
     * @param PriceBuilder $priceBuilder
     * @param EventKeyGenerator $eventKeyGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DownloadableLinkPrice $downloadableLinkPrice,
        StoreManagerInterface $storeManager,
        PriceBuilder $priceBuilder,
        EventKeyGenerator $eventKeyGenerator,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->downloadableLinkPrice = $downloadableLinkPrice;
        $this->storeManager = $storeManager;
        $this->priceBuilder = $priceBuilder;
        $this->eventKeyGenerator = $eventKeyGenerator;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(array $indexData): \Generator
    {
        try {
            foreach (\array_chunk($indexData, self::BATCH_SIZE) as $indexDataChunk) {
                $result = [];
                $queryArguments = [];

                foreach ($indexDataChunk as $data) {
                    $queryArguments[$data['scope_id']][] = $data['entity_id'];
                }

                foreach ($queryArguments as $scopeId => $ids) {
                    $select = $this->downloadableLinkPrice->getQuery($ids, $scopeId);
                    $cursor = $this->resourceConnection->getConnection()->query($select);

                    while ($row = $cursor->fetch()) {
                        $result[$row['entity_id']][$scopeId] = $row['value'];
                    }
                }

                yield $this->getEventsData($indexDataChunk, $result);
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve downloadable link price data.');
        }
    }

    /**
     * Retrieve prices event data
     *
     * @param array $indexData
     * @param array $actualData
     *
     * @return array
     *
     * @throws LocalizedException
     * @throws \InvalidArgumentException
     */
    private function getEventsData(array $indexData, array $actualData): array
    {
        $events = [];

        foreach ($indexData as $data) {
            $value = $actualData[$data['entity_id']][$data['scope_id']] ?? null;
            $eventType = null === $value ? self::EVENT_DOWNLOADABLE_LINK_PRICE_DELETED :
                self::EVENT_DOWNLOADABLE_LINK_PRICE_CHANGED;
            $websiteId = (string)$this->storeManager->getStore($data['scope_id'])->getWebsiteId();
            $key = $this->eventKeyGenerator->generate($eventType, $websiteId, null);
            $events[$key][] = $this->priceBuilder->buildDownloadableLinkPriceEventData($data['entity_id'], $value);
        }

        return $events;
    }
}
