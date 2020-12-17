<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\FullReindex;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\TierPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing product tier prices events
 */
class TierPriceEvent implements FullReindexPriceProviderInterface
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
    public function retrieve(): \Generator
    {
        try {
            $queryArguments = $this->buildQueryArguments();
            foreach ($queryArguments as $scopeId => $indexEntities) {
                $continue = true;
                $lastKnownId = 0;
                while ($continue === true) {
                    $select = $this->tierPrice->getQuery($scopeId, null, $lastKnownId, self::BATCH_SIZE);
                    $cursor = $this->resourceConnection->getConnection()->query($select);
                    $result = [];
                    while ($row = $cursor->fetch()) {
                        $result[$row['scope_id']][$row['entity_id']][$row['customer_group_id']][$row['qty']] = $row;
                    }
                    if (empty($result)) {
                        $continue = false;
                    } else {
                        yield $this->getEventsData($result);
                        $lastKnownId = array_key_last($result[$scopeId]);
                    }
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product tier price data.');
        }
    }

    /**
     * Build query arguments from index data or no data in case of full sync
     *
     * @param array $indexData
     *
     * @return array
     */
    private function buildQueryArguments(): array
    {
        $queryArguments = [];
        foreach ($this->storeManager->getStores(true) as $store) {
            $queryArguments[$store->getId()] = [];
        }
        return $queryArguments;
    }

    /**
     * Form prices event data.
     *
     * @param array $actualData
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getEventsData(array $actualData): array
    {
        $events = [];
        foreach ($actualData as $scopeId => $eventData) {
            $websiteId = (string)$this->storeManager->getStore($scopeId)->getWebsiteId();
            foreach ($eventData as $entityId => $entityData) {
                foreach ($entityData as $customerGroup => $groupData) {
                    foreach ($groupData as $qty => $priceData) {
                        $eventType = $qty > 1 ? self::EVENT_TIER_PRICE_CHANGED : self::EVENT_PRICE_CHANGED;
                        $key = $this->eventKeyGenerator->generate(
                            $eventType,
                            $websiteId,
                            (string)$customerGroup
                        );
                        $events[$key][] = $this->buildEventData(
                            (string)$entityId,
                            $qty,
                            $priceData['group_price_type'],
                            $priceData['value']
                        );
                    }
                }
            }
        }
        return $events;
    }

    /**
     * Build event data.
     *
     * @param string $entityId
     * @param string $qty
     * @param string|null $priceType
     * @param string|null $value
     *
     * @return array
     */
    private function buildEventData(string $entityId, string $qty, ?string $priceType, ?string $value): array
    {
        return [
            'id' => $entityId,
            'attribute_code' => 'tier_price',
            'qty' => $qty,
            'price_type' => $priceType,
            'value' => $value,
        ];
    }
}
