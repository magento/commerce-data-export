<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\CustomOptionPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

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
     * @param ResourceConnection $resourceConnection
     * @param CustomOptionPrice $customOptionPrice
     * @param StoreManagerInterface $storeManager
     * @param EventBuilder $eventBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomOptionPrice $customOptionPrice,
        StoreManagerInterface $storeManager,
        EventBuilder $eventBuilder,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customOptionPrice = $customOptionPrice;
        $this->storeManager = $storeManager;
        $this->eventBuilder = $eventBuilder;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(array $indexData): array
    {
        $events = [];

        try {
            $select = $this->customOptionPrice->getQuery($indexData['entity_id'], $indexData['scope_id']);
            $result = $this->resourceConnection->getConnection()->fetchRow($select) ?: null;
            $events[] = $this->getEventData($indexData, $result);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve product custom options price data.');
        }

        return $events;
    }

    /**
     * Retrieve event data.
     *
     * @param array $indexData
     * @param array|null $result
     *
     * @return array
     *
     * @throws LocalizedException
     */
    private function getEventData(array $indexData, ?array $result): array
    {
        $additionalData = [];
        $scopeCode = $this->storeManager->getStore($indexData['scope_id'])->getWebsite()->getCode();
        $eventType = null === $result ? self::EVENT_CUSTOM_OPTION_PRICE_DELETED :
            self::EVENT_CUSTOM_OPTION_PRICE_CHANGED;

        if (null !== $result) {
            $additionalData['meta']['price_type'] = $result['option_price_type'];
        }

        return $this->eventBuilder->build(
            $eventType,
            $indexData['entity_id'], // TODO base64_encode with correct format
            $scopeCode,
            null,
            $result['value'] ?? null,
            $additionalData
        );
    }
}
