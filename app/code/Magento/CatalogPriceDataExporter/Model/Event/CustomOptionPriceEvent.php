<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\CustomizableEnteredOptionValueUid;
use Magento\CatalogPriceDataExporter\Model\Query\CustomOptionPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Class responsible for providing custom option price events
 */
class CustomOptionPriceEvent implements ProductPriceEventInterface
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomizableEnteredOptionValueUid
     */
    private $optionValueUid;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomOptionPrice $customOptionPrice
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CustomizableEnteredOptionValueUid $optionValueUid
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomOptionPrice $customOptionPrice,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        CustomizableEnteredOptionValueUid $optionValueUid
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customOptionPrice = $customOptionPrice;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->optionValueUid = $optionValueUid;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(array $indexData): array
    {
        $result = [];
        $queryArguments = [];

        try {
            foreach ($indexData as $data) {
                $queryArguments[$data['scope_id']]['optionIds'][] = $data['entity_id'];
            }

            foreach ($queryArguments as $scopeId => $queryData) {
                $select = $this->customOptionPrice->getQuery($queryData['optionIds'], $scopeId);
                $cursor = $this->resourceConnection->getConnection()->query($select);
                while ($row = $cursor->fetch()) {
                    $result[$scopeId][$row['option_id']] = [
                        'option_id' => $row['option_id'],
                        'price' => $row['price'],
                        'price_type' => $row['price_type']
                    ];
                }
            }
            $eventsData = $this->getEventData($result);
            $output = $this->formatEvents($eventsData);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product custom options price data.');
        }

        return $output;
    }

    /**
     * Retrieve prices event data
     *
     * @param array $resultData
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function getEventData(array $resultData): array
    {
        $events = [];
        foreach ($resultData as $scope => $pricesData) {
            foreach ($pricesData as $priceData) {
                $events[self::EVENT_CUSTOM_OPTION_PRICE_CHANGED][$scope][] = $this->buildEventData($priceData);
            }
        }
        return $events;
    }

    /**
     * Build event data
     *
     * @param array $data
     *
     * @return array
     */
    private function buildEventData(array $data): array
    {
        $id = $this->optionValueUid->resolve([CustomizableEnteredOptionValueUid::OPTION_ID => $data['option_id']]);
        return [
            'id' => $id,
            'value' => $data['price'],
            'price_type' => $data['price_type']
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
    private function formatEvents(array $eventsData): array
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
