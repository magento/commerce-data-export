<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\PartialReindex;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\TierPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing product tier prices events
 */
class TierPriceEvent implements PartialReindexPriceProviderInterface
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
     * @param EventKeyGenerator $eventKeyGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TierPrice $tierPrice,
        EventKeyGenerator $eventKeyGenerator,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tierPrice = $tierPrice;
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
                foreach ($queryArguments as $scopeId => $entityIds) {
                    $select = $this->tierPrice->getQuery($entityIds, $scopeId);
                    $cursor = $this->resourceConnection->getConnection()->query($select);

                    while ($row = $cursor->fetch()) {
                        $result[$row['scope_id']][$row['customer_group_id']][$row['entity_id']][$row['qty']] = $row;
                    }
                }
                yield $this->getEventData($indexDataChunk, $result);
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product tier price data.');
        }
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
