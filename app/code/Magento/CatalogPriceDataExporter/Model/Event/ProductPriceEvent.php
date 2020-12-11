<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\Query\ProductPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing product price / special price events
 */
class ProductPriceEvent implements ProductPriceEventInterface
{
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductPrice $productPrice
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductPrice $productPrice,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productPrice = $productPrice;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(array $indexData): array
    {
        $result = [];
        $queryArguments = [];

        try {
            foreach ($indexData as &$data) {
                $data['attributes'] = \array_unique(\explode(',', $data['attributes']));
                $queryArguments[$data['scope_id']]['ids'][] = $data['entity_id'];
                $queryArguments[$data['scope_id']]['attributes'][] = $data['attributes'];
            }

            foreach ($queryArguments as $scopeId => $queryData) {
                $attributes = \array_merge($queryData['attributes']);
                $select = $this->productPrice->getQuery($queryData['ids'], $scopeId, $attributes);
                $cursor = $this->resourceConnection->getConnection()->query($select);
                while ($row = $cursor->fetch()) {
                    $result[$scopeId][$row['entity_id']][$row['attribute_code']] = $row['value'];
                }
            }

            $eventsData = $this->getEventsData($indexData, $result);
            $output = $this->formatEvents($eventsData);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product price data.');
        }

        return $output;
    }

    /**
     * Retrieve prices event data
     *
     * @param array $indexData
     * @param array $actualData
     *
     * @return array
     */
    private function getEventsData(array $indexData, array $actualData): array
    {
        $eventsData = [];
        foreach ($indexData as $data) {
            foreach ($data['attributes'] as $attributeCode) {
                $scope = $data['scope_id'];
                $value = $actualData[$scope][$data['entity_id']][$attributeCode] ?? null;
                $eventType = $value === null ? self::EVENT_PRICE_DELETED : self::EVENT_PRICE_CHANGED;
                $eventsData[$eventType][$scope][] = $this->buildEventData($attributeCode, $data, $value);
            }
        }
        return $eventsData;
    }

    /**
     * Build event data.
     *
     * @param string $attributeCode
     * @param array $indexData
     * @param string|null $attributeValue
     *
     * @return array
     */
    private function buildEventData(string $attributeCode, array $indexData, ?string $attributeValue): array
    {
        return [
            'id' => $indexData['entity_id'],
            'value' => $attributeValue,
            'attribute_code' => $attributeCode
        ];
    }

    /**
     * Format events output
     *
     * @param array $eventsData
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function formatEvents(array $eventsData) : array
    {
        $output = [];
        foreach ($eventsData as $eventType => $event) {
            foreach ($event as $scopeId => $eventData) {
                $scopeCode = $this->storeManager->getStore($scopeId)->getWebsite()->getCode();
                $output[$eventType][] = [
                    'meta' => [
                        'event_type' => $eventType,
                        'website' => $scopeCode === WebsiteInterface::ADMIN_CODE ? null : $scopeCode,
                        'customer_group' => null,
                    ],
                    'data' => $eventData
                ];
            }
        }
        return $output;
    }
}
