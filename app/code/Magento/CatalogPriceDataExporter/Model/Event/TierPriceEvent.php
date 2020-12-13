<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\TierPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
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
     * @var EventKeyGenerator
     */
    private $eventKeyGenerator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param TierPrice $tierPrice
     * @param StoreManagerInterface $storeManager
     * @param EventKeyGenerator $eventKeyGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TierPrice $tierPrice,
        StoreManagerInterface $storeManager,
        EventKeyGenerator $eventKeyGenerator,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tierPrice = $tierPrice;
        $this->storeManager = $storeManager;
        $this->eventKeyGenerator = $eventKeyGenerator;
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

            $output = $this->getEventData($indexData, $result);
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

        foreach ($indexData as $data) {
            $row = $actualData[$data['scope_id']][$data['customer_group']][$data['entity_id']][$data['qty']] ?? null;
            $eventType = $this->resolveEventType($data['qty'], $row);

            $key = $this->eventKeyGenerator->generate($eventType, $data['scope_id'], $data['customer_group']);
            $events[$key][] = $this->buildEventData($data, $row);
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
    private function resolveEventType(string $qty, ?array $data): string
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
        return [
            'id' => $indexData['entity_id'],
            'attribute_code' => 'tier_price',
            'qty' => $indexData['qty'],
            'price_type' => $data['group_price_type'] ?? null,
            'value' => $data['value'] ?? null,
        ];
    }
}
