<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\CustomizableSelectedOptionValueUid;
use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\CustomOptionTypePrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var EventBuilder
     */
    private $eventBuilder;

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
     * @param EventBuilder $eventBuilder
     * @param LoggerInterface $logger
     * @param CustomizableSelectedOptionValueUid $optionValueUid
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomOptionTypePrice $customOptionTypePrice,
        StoreManagerInterface $storeManager,
        EventBuilder $eventBuilder,
        LoggerInterface $logger,
        CustomizableSelectedOptionValueUid $optionValueUid
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customOptionTypePrice = $customOptionTypePrice;
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
                $queryArguments[$data['scope_id']]['optionTypeIds'][] = $data['entity_id'];
            }

            foreach ($queryArguments as $scopeId => $queryData) {
                $select = $this->customOptionTypePrice->getQuery($queryData['optionTypeIds'], $scopeId);
                $cursor = $this->resourceConnection->getConnection()->query($select);
                while ($row = $cursor->fetch()) {
                    $result[$row['option_type_id']][$scopeId]['price'] = $row['price'];
                    $result[$row['option_type_id']][$scopeId]['price_type'] = $row['price_type'];
                    $result[$row['option_type_id']][$scopeId]['option_id'] = $row['option_id'];
                }
            }

            $events = $this->getEventsData($indexData, $result);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product custom option types price data.');
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
            $optionId = $actualData[$data['entity_id']][$data['scope_id']]['option_id'] ?? null;

            if ($price !== null && $priceType !== null && $optionId !== null) {
                $events[] = $this->buildEventData($data, $price, $priceType, $optionId);
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
     * @param string $optionId
     *
     * @return array
     *
     * @throws NoSuchEntityException
     * @throws \InvalidArgumentException
     */
    private function buildEventData(array $indexData, string $price, string $priceType, string $optionId): array
    {
        $scopeCode = $this->storeManager->getStore($indexData['scope_id'])->getWebsite()->getCode();
        $id = $this->optionValueUid->resolve([
            CustomizableSelectedOptionValueUid::OPTION_ID => $optionId,
            CustomizableSelectedOptionValueUid::OPTION_VALUE_ID => $indexData['entity_id']
        ]);

        return $this->eventBuilder->build(
            self::EVENT_CUSTOM_OPTION_TYPE_PRICE_CHANGED,
            $id,
            $scopeCode,
            null,
            $price,
            ['meta' => ['price_type' => $priceType]]
        );
    }
}
