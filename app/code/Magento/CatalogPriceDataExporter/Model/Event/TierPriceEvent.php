<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\TierPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
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
     * @var EventBuilder
     */
    private $eventBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param TierPrice $tierPrice
     * @param StoreManagerInterface $storeManager
     * @param EventBuilder $eventBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TierPrice $tierPrice,
        StoreManagerInterface $storeManager,
        EventBuilder $eventBuilder,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tierPrice = $tierPrice;
        $this->storeManager = $storeManager;
        $this->eventBuilder = $eventBuilder;
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
                    $result[$row['entity_id']][$row['scope_id']][$row['qty']][$row['customer_group_id']] = $row;
                }
            }

            $events = $this->getEventData($indexData, $result);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product tier price data.');
        }

        return $events;
    }

    /**
     * Retrieve prices event data
     *
     * @param array $indexData
     * @param array $actualData
     *
     * @return array
     *
     * @throws LocalizedException
     */
    private function getEventData(array $indexData, array $actualData): array
    {
        $events = [];

        foreach ($indexData as $data) {
            $row = $actualData[$data['entity_id']][$data['scope_id']][$data['qty']][$data['customer_group']] ?? null;
            $events[] = $this->buildEventData($data, $row);
        }

        return $events;
    }

    /**
     * Build event data.
     *
     * @param array $indexData
     * @param array|null $result
     *
     * @return array
     *
     * @throws LocalizedException
     */
    private function buildEventData(array $indexData, ?array $result): array
    {
        $additionalData = [];
        $scopeCode = $this->storeManager->getWebsite($indexData['scope_id'])->getCode();

        // TODO refactor
        if (null === $result || null === $result['value']) {
            $eventType = $indexData['qty'] > 1 ? self::EVENT_TIER_PRICE_DELETED : self::EVENT_PRICE_DELETED;
        } else {
            $eventType = $indexData['qty'] > 1 ? self::EVENT_TIER_PRICE_CHANGED : self::EVENT_PRICE_CHANGED;
        }

        if (null !== $result) {
            $additionalData['meta']['price_type'] = $result['group_price_type'];
        }

        if ($indexData['qty'] > 1) {
            $additionalData['data']['qty'] = $indexData['qty'];
        } else {
            $additionalData['meta']['code'] = 'tier_price';
        }

        return $this->eventBuilder->build(
            $eventType,
            $indexData['entity_id'],
            $scopeCode,
            $indexData['customer_group'],
            $result['value'] ?? null,
            $additionalData
        );
    }
}
