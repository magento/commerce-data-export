<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\ProductPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing product price / special price events
 */
class ProductPriceEvent implements ProductPriceEventInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductPrice
     */
    private $productPrice;

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
     * @param ProductPrice $productPrice
     * @param StoreManagerInterface $storeManager
     * @param EventBuilder $eventBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductPrice $productPrice,
        StoreManagerInterface $storeManager,
        EventBuilder $eventBuilder,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productPrice = $productPrice;
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
            foreach ($indexData as &$data) {
                $data['attributes'] = \array_unique(\explode(',', $data['attributes']));
                $queryArguments[$data['scope_id']]['ids'][] = $data['entity_id'];
                $queryArguments[$data['scope_id']]['attributes'][] = $data['attributes'];
            }

            foreach ($queryArguments as $scopeId => $queryData) {
                $attributes = \array_merge($queryData['attributes']);
                $select = $this->productPrice->getQuery($queryData['ids'], $scopeId, $attributes);
                $cursor = $this->resourceConnection->getConnection()->query($select);

                while ($row = $cursor->fetch()) {
                    $result[$row['entity_id']][$scopeId][$row['attribute_code']] = $row['value'];
                }
            }

            $events = $this->getEventsData($indexData, $result);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product price data.');
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
     */
    private function getEventsData(array $indexData, array $actualData): array
    {
        $events = [];

        foreach ($indexData as $data) {
            foreach ($data['attributes'] as $attributeCode) {
                $value = $actualData[$data['entity_id']][$data['scope_id']][$attributeCode] ?? null;
                $events[] = $this->buildEventData($attributeCode, $data, $value);
            }
        }

        return $events;
    }

    /**
     * Build event data.
     *
     * @param string $attributeCode
     * @param array $indexData
     * @param string|null $attributeValue
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function buildEventData(string $attributeCode, array $indexData, ?string $attributeValue): array
    {
        $scopeCode = $this->storeManager->getStore($indexData['scope_id'])->getWebsite()->getCode();
        $eventType = null === $attributeValue ? self::EVENT_PRICE_DELETED : self::EVENT_PRICE_CHANGED;

        return $this->eventBuilder->build(
            $eventType,
            $indexData['entity_id'],
            $scopeCode,
            null,
            $attributeValue,
            ['meta' => ['code' => $attributeCode]]
        );
    }
}
