<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\ProductPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing product price / special price events
 */
class ProductPriceEvent implements ProductPriceEventInterface
{
    /**
     * Default price attributes
     */
    private const PRICE_ATTRIBUTES = [['price'], ['special_price']];

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductPrice
     */
    private $productPrice;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @param ProductPrice $productPrice
     * @param StoreManagerInterface $storeManager
     * @param EventKeyGenerator $eventKeyGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductPrice $productPrice,
        StoreManagerInterface $storeManager,
        EventKeyGenerator $eventKeyGenerator,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productPrice = $productPrice;
        $this->storeManager = $storeManager;
        $this->eventKeyGenerator = $eventKeyGenerator;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(?array $indexData = []): \Generator
    {
        try {
            $queryArguments = $this->buildQueryArguments($indexData);
            foreach ($queryArguments as $scopeId => $queryData) {
                $attributes = \array_merge($queryData['attributes']);
                $continue = true;
                $lastKnownId = 0;
                while ($continue === true) {
                    $select = $this->productPrice->getQuery(
                        $queryData['ids'],
                        $scopeId,
                        $attributes,
                        $lastKnownId,
                        self::BATCH_SIZE
                    );
                    $cursor = $this->resourceConnection->getConnection()->query($select);
                    $result = [];
                    while ($row = $cursor->fetch()) {
                        $result[$scopeId][$row['entity_id']][$row['attribute_code']] = $row['value'];
                    }
                    if (empty($result)) {
                        $continue = false;
                    } else {
                        yield $this->getEventsData($indexData, $result);
                        $lastKnownId = array_key_last($result[$scopeId]);
                    }
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product price data.');
        }
    }

    /**
     * Build query arguments from index data or no data in case of full sync
     *
     * @param array $indexData
     *
     * @return array
     */
    private function buildQueryArguments(array &$indexData): array
    {
        $queryArguments = [];
        if (empty($indexData)) {
            foreach ($this->storeManager->getStores(true) as $store) {
                $storeId = $store->getId();
                $queryArguments[$storeId]['attributes'] = self::PRICE_ATTRIBUTES;
                $queryArguments[$storeId]['ids'] = [];
            }
        } else {
            foreach ($indexData as &$data) {
                $data['attributes'] = \array_unique(\explode(',', $data['attributes']));
                $queryArguments[$data['scope_id']]['attributes'][] = $data['attributes'];
                $queryArguments[$data['scope_id']]['ids'][] = $data['entity_id'];
            }
        }
        return $queryArguments;
    }

    /**
     * Retrieve prices event data. If indexData is empty then all data is used.
     *
     * @param array $indexData
     * @param array $actualData
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function getEventsData(array $indexData, array $actualData): array
    {
        $events = [];
        if (empty($indexData)) {
            foreach ($actualData as $scopeId => $eventData) {
                $websiteId = (string)$this->storeManager->getStore($scopeId)->getWebsiteId();
                foreach ($eventData as $entityId => $priceData) {
                    foreach ($priceData as $attributeCode => $value) {
                        $key = $this->eventKeyGenerator->generate(self::EVENT_PRICE_CHANGED, $websiteId, null);
                        $events[$key][] = $this->buildEventData((string)$entityId, $attributeCode, $value);
                    }
                }
            }
        } else {
            foreach ($indexData as $data) {
                $websiteId = (string)$this->storeManager->getStore($data['scope_id'])->getWebsiteId();
                foreach ($data['attributes'] as $attributeCode) {
                    $value = $actualData[$data['scope_id']][$data['entity_id']][$attributeCode] ?? null;
                    $eventType = $value === null ? self::EVENT_PRICE_DELETED : self::EVENT_PRICE_CHANGED;
                    $key = $this->eventKeyGenerator->generate($eventType, $websiteId, null);
                    $events[$key][] = $this->buildEventData($data['entity_id'], $attributeCode, $value);
                }
            }
        }
        return $events;
    }

    /**
     * Build event data.
     *
     * @param string $entityId
     * @param string $attributeCode
     * @param string|null $attributeValue
     *
     * @return array
     */
    private function buildEventData(string $entityId, string $attributeCode, ?string $attributeValue): array
    {
        return [
            'id' => $entityId,
            'attribute_code' => $attributeCode,
            'value' => $attributeValue,
        ];
    }
}
