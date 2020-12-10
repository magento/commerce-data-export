<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\DownloadableLinksOptionUid;
use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\DownloadableLinkPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing downloadable product link price events
 */
class DownloadableLinkPriceEvent implements ProductPriceEventInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DownloadableLinkPrice
     */
    private $downloadableLinkPrice;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EventBuilder
     */
    private $eventBuilder;

    /**
     * @var DownloadableLinksOptionUid
     */
    private $downloadableLinksOptionUid;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param DownloadableLinkPrice $downloadableLinkPrice
     * @param StoreManagerInterface $storeManager
     * @param EventBuilder $eventBuilder
     * @param DownloadableLinksOptionUid $downloadableLinksOptionUid
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DownloadableLinkPrice $downloadableLinkPrice,
        StoreManagerInterface $storeManager,
        EventBuilder $eventBuilder,
        DownloadableLinksOptionUid $downloadableLinksOptionUid,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->downloadableLinkPrice = $downloadableLinkPrice;
        $this->storeManager = $storeManager;
        $this->eventBuilder = $eventBuilder;
        $this->downloadableLinksOptionUid = $downloadableLinksOptionUid;
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

            foreach ($queryArguments as $scopeId => $ids) {
                $select = $this->downloadableLinkPrice->getQuery($ids, $scopeId);
                $cursor = $this->resourceConnection->getConnection()->query($select);

                while ($row = $cursor->fetch()) {
                    $result[$row['entity_id']][$scopeId] = $row['value'];
                }
            }

            $events = $this->getEventsData($indexData, $result);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve downloadable link price data.');
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
     * @throws \InvalidArgumentException
     */
    private function getEventsData(array $indexData, array $actualData): array
    {
        $events = [];

        foreach ($indexData as $data) {
            $value = $actualData[$data['entity_id']][$data['scope_id']] ?? null;
            $events[] = $this->buildEventData($data, $value);
        }

        return $events;
    }

    /**
     * Build event data.
     *
     * @param array $indexData
     * @param string|null $value
     *
     * @return array
     *
     * @throws LocalizedException
     * @throws \InvalidArgumentException
     */
    private function buildEventData(array $indexData, ?string $value): array
    {
        $scopeCode = $this->storeManager->getWebsite($indexData['scope_id'])->getCode();
        $eventType = null === $value ? self::EVENT_DOWNLOADABLE_LINK_PRICE_DELETED :
            self::EVENT_DOWNLOADABLE_LINK_PRICE_CHANGED;

        return $this->eventBuilder->build(
            $eventType,
            $this->downloadableLinksOptionUid->resolve(
                [
                    DownloadableLinksOptionUid::OPTION_ID => $indexData['entity_id'],
                ]
            ),
            $scopeCode,
            null,
            $value
        );
    }
}
