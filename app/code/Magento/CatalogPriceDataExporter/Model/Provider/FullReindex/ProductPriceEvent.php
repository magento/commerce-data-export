<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\FullReindex;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\ProductPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing product price / special price events for full indexation
 */
class ProductPriceEvent implements FullReindexPriceProviderInterface
{
    /**
     * Product price eav attributes
     */
    public const PRICE_ATTRIBUTES = ['price', 'special_price'];

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
    public function retrieve(): \Generator
    {
        try {
            foreach ($this->storeManager->getStores(true) as $store) {
                $storeId = (int)$store->getId();
                $continue = true;
                $lastKnownId = 0;
                while ($continue === true) {
                    $select = $this->productPrice->getQuery(
                        [],
                        $storeId,
                        self::PRICE_ATTRIBUTES,
                        $lastKnownId,
                        self::BATCH_SIZE
                    );
                    $cursor = $this->resourceConnection->getConnection()->query($select);
                    $result = [];
                    while ($row = $cursor->fetch()) {
                        $result[$row['entity_id']][$row['attribute_code']] = $row['value'];
                    }
                    if (empty($result)) {
                        $continue = false;
                    } else {
                        yield $this->getEventsData($result, $storeId);
                        $lastKnownId = array_key_last($result);
                    }
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product price data for full sync.');
        }
    }

    /**
     * Retrieve prices event data.
     *
     * @param array $data
     * @param int $storeId
     *
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function getEventsData(array $data, int $storeId): array
    {
        $events = [];
        $websiteId = (string)$this->storeManager->getStore($storeId)->getWebsiteId();
        $key = $this->eventKeyGenerator->generate(self::EVENT_PRICE_CHANGED, $websiteId, null);
        foreach ($data as $entityId => $priceData) {
            foreach ($priceData as $attributeCode => $value) {
                $events[$key][] = $this->buildEventData((string)$entityId, $attributeCode, $value);
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
