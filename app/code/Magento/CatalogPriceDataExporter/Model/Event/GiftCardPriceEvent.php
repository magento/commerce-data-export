<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\GiftCardPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing gift card price events
 */
class GiftCardPriceEvent implements ProductPriceEventInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GiftCardPrice
     */
    private $giftCardPrice;

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
     * @param GiftCardPrice $giftCardPrice
     * @param EventKeyGenerator $eventKeyGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GiftCardPrice $giftCardPrice,
        EventKeyGenerator $eventKeyGenerator,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->giftCardPrice = $giftCardPrice;
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
                $queryArguments[$data['scope_id']]['ids'][] = $data['entity_id'];
                $queryArguments[$data['scope_id']]['attributes'][] = $data['attribute'];
            }

            foreach ($queryArguments as $scopeId => $queryData) {
                $select = $this->giftCardPrice->getQuery($queryData['ids'], $scopeId, $queryData['attributes']);
                $cursor = $this->resourceConnection->getConnection()->query($select);

                while ($row = $cursor->fetch()) {
                    $result[$scopeId][$row['entity_id']][$row['attribute_code']][$row['value']] = true;
                }
            }

            $output = $this->getEventsData($indexData, $result);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve gift card price data.');
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
        $events = [];

        foreach ($indexData as $data) {
            $value = $actualData[$data['scope_id']][$data['entity_id']][$data['attribute']][$data['value']] ?? null;
            $eventType = $value === null ? self::EVENT_GIFT_CARD_PRICE_DELETED : self::EVENT_GIFT_CARD_PRICE_CHANGED;
            $key = $this->eventKeyGenerator->generate($eventType, $data['scope_id'], null);
            $events[$key][] = $this->buildEventData($data['entity_id'], $data['attribute'], $data['value']);
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
