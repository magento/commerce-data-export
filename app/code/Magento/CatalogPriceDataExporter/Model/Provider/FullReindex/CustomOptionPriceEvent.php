<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\FullReindex;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Indexer\PriceBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\CustomOptionPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing custom option price events for full indexation
 */
class CustomOptionPriceEvent implements FullReindexPriceProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CustomOptionPrice
     */
    private $customOptionPrice;

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
     * @param CustomOptionPrice $customOptionPrice
     * @param StoreManagerInterface $storeManager
     * @param PriceBuilder $priceBuilder
     * @param EventKeyGenerator $eventKeyGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomOptionPrice $customOptionPrice,
        StoreManagerInterface $storeManager,
        PriceBuilder $priceBuilder,
        EventKeyGenerator $eventKeyGenerator,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customOptionPrice = $customOptionPrice;
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
                    $select = $this->customOptionPrice->getQuery([], $storeId, $lastKnownId, self::BATCH_SIZE);
                    $cursor = $this->resourceConnection->getConnection()->query($select);
                    while ($row = $cursor->fetch()) {
                        $result[$row['option_id']] = [
                            'option_id' => $row['option_id'],
                            'price' => $row['price'],
                            'price_type' => $row['price_type'],
                        ];
                    }
                    if (empty($result)) {
                        $continue = false;
                    } else {
                        yield $this->getEventData($result, $storeId);
                        $lastKnownId = array_key_last($result);
                    }
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product custom options price data.');
        }
    }

    /**
     * Retrieve prices event data
     *
     * @param array $resultData
     * @param int $storeId
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function getEventData(array $resultData, int $storeId): array
    {
        $events = [];
        $websiteId = (string)$this->storeManager->getStore($storeId)->getWebsiteId();
        $key = $this->eventKeyGenerator->generate(self::EVENT_CUSTOM_OPTION_PRICE_CHANGED, $websiteId, null);
        foreach ($resultData as $priceData) {
            $events[$key][] = $this->priceBuilder->buildCustomOptionPriceEventData($priceData);
        }
        return $events;
    }
}
