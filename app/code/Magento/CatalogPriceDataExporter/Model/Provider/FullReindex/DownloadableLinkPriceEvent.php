<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\FullReindex;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Indexer\PriceBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\DownloadableLinkPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing downloadable product link price events for full indexation
 */
class DownloadableLinkPriceEvent implements FullReindexPriceProviderInterface
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
    public function retrieve(): \Generator
    {
        try {
            foreach ($this->storeManager->getStores(true) as $store) {
                $storeId = (int)$store->getId();
                $continue = true;
                $lastKnownId = 0;
                while ($continue === true) {
                    $result = [];
                    $select = $this->downloadableLinkPrice->getQuery([], $storeId, (int)$lastKnownId, self::BATCH_SIZE);
                    $cursor = $this->resourceConnection->getConnection()->query($select);
                    while ($row = $cursor->fetch()) {
                        $result[$row['entity_id']] = $row['value'];
                        $lastKnownId = $row['link_id'];
                    }
                    if (empty($result)) {
                        $continue = false;
                    } else {
                        yield $this->getEventsData($result, $storeId);
                    }
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve downloadable link price data for full sync.');
        }
    }

    /**
     * Retrieve prices event data
     *
     * @param array $actualData
     * @param int $storeId
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function getEventsData(array $actualData, int $storeId): array
    {
        $events = [];
        $websiteId = (string)$this->storeManager->getStore($storeId)->getWebsiteId();
        $key = $this->eventKeyGenerator->generate(
            self::EVENT_DOWNLOADABLE_LINK_PRICE_CHANGED,
            $websiteId,
            null
        );
        foreach ($actualData as $entityId => $value) {
            $events[$key][] = $this->priceBuilder->buildDownloadableLinkPriceEventData((string)$entityId, $value);
        }
        return $events;
    }
}
