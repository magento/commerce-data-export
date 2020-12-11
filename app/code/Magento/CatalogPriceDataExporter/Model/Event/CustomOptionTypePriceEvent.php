<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\CustomizableSelectedOptionValueUid;
use Magento\CatalogPriceDataExporter\Model\Query\CustomOptionTypePrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing custom selectable option price events
 */
class CustomOptionTypePriceEvent implements ProductPriceEventInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CustomOptionTypePrice
     */
    private $customOptionTypePrice;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomizableSelectedOptionValueUid
     */
    private $optionValueUid;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomOptionTypePrice $customOptionTypePrice
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CustomizableSelectedOptionValueUid $optionValueUid
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomOptionTypePrice $customOptionTypePrice,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        CustomizableSelectedOptionValueUid $optionValueUid
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customOptionTypePrice = $customOptionTypePrice;
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
                $queryArguments[$data['scope_id']]['optionTypeIds'][] = $data['entity_id'];
            }

            foreach ($queryArguments as $scopeId => $queryData) {
                $select = $this->customOptionTypePrice->getQuery($queryData['optionTypeIds'], $scopeId);
                $cursor = $this->resourceConnection->getConnection()->query($select);
                while ($row = $cursor->fetch()) {
                    $result[$scopeId][$row['option_type_id']] = [
                        'option_id' => $row['option_id'],
                        'option_type_id' => $row['option_type_id'],
                        'price' => $row['price'],
                        'price_type' => $row['price_type']
                    ];
                }
            }

            $eventsData = $this->getEventsData($result);
            $output = $this->formatEvents($eventsData);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product custom option types price data.');
        }

        return $output;
    }

    /**
     * Retrieve prices event data
     *
     * @param array $resultData
     *
     * @return array
     */
    private function getEventsData(array $resultData): array
    {
        $events = [];
        foreach ($resultData as $scope => $data) {
            foreach ($data as $priceData) {
                $events[self::EVENT_CUSTOM_OPTION_TYPE_PRICE_CHANGED][$scope] = $this->buildEventData(
                    $priceData,
                );
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
        $id = $this->optionValueUid->resolve([
            CustomizableSelectedOptionValueUid::OPTION_ID => $data['option_id'],
            CustomizableSelectedOptionValueUid::OPTION_VALUE_ID => $data['option_type_id']
        ]);

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
