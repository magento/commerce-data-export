<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\PartialReindex;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\ProductPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing product price / special price events
 */
class ProductPriceEvent implements PartialReindexPriceProviderInterface
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
     * @var EventKeyGenerator
     */
    private $eventKeyGenerator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductPrice $productPrice
     * @param StoreManagerInterface $storeManager
     * @param EventKeyGenerator $eventKeyGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductPrice $productPrice,
        StoreManagerInterface $storeManager,
        EventKeyGenerator $eventKeyGenerator,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productPrice = $productPrice;
        $this->storeManager = $storeManager;
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
                foreach ($indexDataChunk as &$data) {
                    $data['attributes'] = \array_unique(\explode(',', $data['attributes']));
                    $queryArguments[$data['scope_id']]['ids'][] = $data['entity_id'];
                    $queryArguments[$data['scope_id']]['attributes'][] = $data['attributes'];
                }
                foreach ($queryArguments as $scopeId => $queryData) {
                    $attributes = \array_merge($queryData['attributes']);
                    $select = $this->productPrice->getQuery($queryData['ids'], $scopeId, $attributes);
                    $cursor = $this->resourceConnection->getConnection()->query($select);

                    while ($row = $cursor->fetch()) {
                        $result[$scopeId][$row['entity_id']][$row['attribute_code']] = $row['value'];
                    }
                }
                yield $this->getEventsData($indexDataChunk, $result);
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product price data.');
        }
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
                $value = $actualData[$data['scope_id']][$data['entity_id']][$attributeCode] ?? null;
                $eventType = $value === null ? self::EVENT_PRICE_DELETED : self::EVENT_PRICE_CHANGED;
                $websiteId = (string)$this->storeManager->getStore($data['scope_id'])->getWebsiteId();
                $key = $this->eventKeyGenerator->generate($eventType, $websiteId, null);
                $events[$key][] = $this->buildEventData($data['entity_id'], $attributeCode, $value);
            }
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
