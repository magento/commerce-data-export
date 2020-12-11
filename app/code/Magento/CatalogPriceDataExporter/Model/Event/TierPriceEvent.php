<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\Query\TierPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing product tier prices events
 */
class TierPriceEvent implements ProductPriceEventInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var TierPrice
     */
    private $tierPrice;

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
     * @param TierPrice $tierPrice
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TierPrice $tierPrice,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tierPrice = $tierPrice;
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
            foreach ($indexData as $data) {
                $queryArguments[$data['scope_id']][] = $data['entity_id'];
            }

            foreach ($queryArguments as $scopeId => $entityIds) {
                $select = $this->tierPrice->getQuery($entityIds, $scopeId);
                $cursor = $this->resourceConnection->getConnection()->query($select);
                while ($row = $cursor->fetch()) {
                    $result[$row['scope_id']][$row['customer_group_id']][$row['entity_id']][$row['qty']] = $row;
                }
            }

            $eventsData = $this->getEventData($indexData, $result);
            $output = $this->formatEvents($eventsData);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product tier price data.');
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
    private function getEventData(array $indexData, array $actualData): array
    {
        $events = [];
        foreach ($indexData as $indexDatum) {
            $customerGroup = $indexDatum['customer_group'];
            $scope = $indexDatum['scope_id'];
            $qty = $indexDatum['qty'];
            $data = $actualData[$scope][$customerGroup][$indexDatum['entity_id']][$qty] ?? null;
            $eventType = $this->resolveEventType($qty, $data);
            $events[$eventType][$scope][$customerGroup][] = $this->buildEventData($indexDatum, $data);
        }
        return $events;
    }

    /**
     * Resolve event type
     *
     * @param string $qty
     * @param array|null $data
     *
     * @return string
     */
    private function resolveEventType(string $qty, ?array $data)
    {
        if ($qty > 1) {
            return $data === null ? self::EVENT_TIER_PRICE_DELETED : self::EVENT_TIER_PRICE_CHANGED;
        }
        return $data === null ? self::EVENT_PRICE_DELETED : self::EVENT_PRICE_CHANGED;
    }

    /**
     * Build event data.
     *
     * @param array $indexData
     * @param array|null $data
     *
     * @return array
     */
    private function buildEventData(array $indexData, ?array $data): array
    {

        //todo: Remove the qty, if its 1. remove the 'tier_price' if its 1?.
        return [
            'id' => $indexData['entity_id'],
            'value' => $data['value'] ?? null,
            'attribute_code' => 'tier_price',
            'qty' => $indexData['qty'],
            'price_type' => $data['group_price_type'] ?? null
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
     * todo: Unify these functions across providers
     */
    private function formatEvents(array $eventsData) : array
    {
        $output = [];
        foreach ($eventsData as $eventType => $event) {
            foreach ($event as $scopeId => $eventData) {
                foreach ($eventData as $customerGroup => $eventDatum) {
                    $scopeCode = $this->storeManager->getStore($scopeId)->getWebsite()->getCode();
                    $output[$eventType][] = [
                        'meta' => [
                            'event_type' => $eventType,
                            'website' => $scopeCode === WebsiteInterface::ADMIN_CODE ? null : $scopeCode,
                            'customer_group' => $customerGroup,
                        ],
                        'data' => $eventData
                    ];
                }
            }
        }
        return $output;
    }
}
