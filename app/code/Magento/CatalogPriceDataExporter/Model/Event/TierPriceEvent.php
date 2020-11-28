<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\TierPrice;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

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
    public function retrieve(array $data): array
    {
        $events = [];

        try {
            $result = $this->resourceConnection->getConnection()->fetchRow($this->tierPrice->getQuery($data)) ?: null;
            $events[] = $this->getEventData($data, $result);
        } catch (\Throwable $exception) {
            // TODO log error, throw exception
            $this->logger->error('Error retrieving product tier price data.', ['exception' => $exception->getMessage()]);
        }

        return $events;
    }

    /**
     * Retrieve event data.
     *
     * @param array $data
     * @param array|null $result
     *
     * @return array
     *
     * @throws LocalizedException
     */
    private function getEventData(array $data, ?array $result): array
    {
        $additionalData = [];
        $scopeCode = $this->storeManager->getWebsite($data['scope_id'])->getCode();
        $customerGroupId = true === (bool)$data['all_groups'] ? null : $data['customer_group_id'];

        // TODO refactor
        if (null === $result || null === $result['value']) {
            $eventType = $data['qty'] > 1 ? self::EVENT_TIER_PRICE_DELETED : self::EVENT_PRICE_DELETED;
        } else {
            $eventType = $data['qty'] > 1 ? self::EVENT_TIER_PRICE_CHANGED : self::EVENT_PRICE_CHANGED;
        }

        if (null !== $result) {
            $additionalData['meta']['price_type'] = $result['group_price_type'];
        }

        if ($data['qty'] > 1) {
            $additionalData['data']['qty'] = $data['qty'];
        }

        return $this->eventBuilder->build(
            $eventType,
            $data['entity_id'],
            $scopeCode,
            $customerGroupId,
            $result['value'] ?? null,
            $additionalData
        );
    }
}
