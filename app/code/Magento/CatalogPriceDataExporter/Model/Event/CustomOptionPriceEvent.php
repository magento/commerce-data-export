<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\CustomizableEnteredOptionValueUid;
use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\CustomOptionPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

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
     * @var EventBuilder
     */
    private $eventBuilder;

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
     * @param EventBuilder $eventBuilder
     * @param LoggerInterface $logger
     * @param CustomizableEnteredOptionValueUid $optionValueUid
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomOptionPrice $customOptionPrice,
        StoreManagerInterface $storeManager,
        EventBuilder $eventBuilder,
        LoggerInterface $logger,
        CustomizableEnteredOptionValueUid $optionValueUid
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customOptionPrice = $customOptionPrice;
        $this->storeManager = $storeManager;
        $this->eventBuilder = $eventBuilder;
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
                    $result[$row['option_id']][$scopeId]['price'] = $row['price'];
                    $result[$row['option_id']][$scopeId]['price_type'] = $row['price_type'];
                }
            }

            $events = $this->getEventsData($indexData, $result);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product custom options price data.');
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
     * @throws NoSuchEntityException
     * @throws \InvalidArgumentException
     */
    private function getEventsData(array $indexData, array $actualData): array
    {
        $events = [];
        foreach ($indexData as $data) {
            $price = $actualData[$data['entity_id']][$data['scope_id']]['price'] ?? null;
            $priceType = $actualData[$data['entity_id']][$data['scope_id']]['price_type'] ?? null;

            if ($price !== null && $priceType !== null) {
                $events[] = $this->buildEventData($data, $price, $priceType);
            }
        }

        return $events;
    }

    /**
     * Build event data.
     *
     * @param array $indexData
     * @param string $price
     * @param string $priceType
     *
     * @return array
     *
     * @throws NoSuchEntityException
     * @throws \InvalidArgumentException
     */
    private function buildEventData(array $indexData, string $price, string $priceType): array
    {
        $scopeCode = $this->storeManager->getStore($indexData['scope_id'])->getWebsite()->getCode();
        $id = $this->optionValueUid->resolve([CustomizableEnteredOptionValueUid::OPTION_ID => $indexData['entity_id']]);

        return $this->eventBuilder->build(
            self::EVENT_CUSTOM_OPTION_PRICE_CHANGED,
            $id,
            $scopeCode,
            null,
            $price,
            ['meta' => ['price_type' => $priceType]]
        );
    }
}
