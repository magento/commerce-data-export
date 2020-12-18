<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\PartialReindex;

use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\DownloadableLinksOptionUid;
use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\DownloadableLinkPrice;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing downloadable product link price events
 */
class DownloadableLinkPriceEvent implements PartialReindexPriceProviderInterface
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
     * @var DownloadableLinksOptionUid
     */
    private $downloadableLinksOptionUid;

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
     * @param DownloadableLinkPrice $downloadableLinkPrice
     * @param StoreManagerInterface $storeManager
     * @param DownloadableLinksOptionUid $downloadableLinksOptionUid
     * @param EventKeyGenerator $eventKeyGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DownloadableLinkPrice $downloadableLinkPrice,
        StoreManagerInterface $storeManager,
        DownloadableLinksOptionUid $downloadableLinksOptionUid,
        EventKeyGenerator $eventKeyGenerator,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->downloadableLinkPrice = $downloadableLinkPrice;
        $this->storeManager = $storeManager;
        $this->downloadableLinksOptionUid = $downloadableLinksOptionUid;
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

                foreach ($indexDataChunk as $data) {
                    $queryArguments[$data['scope_id']][] = $data['entity_id'];
                }

                foreach ($queryArguments as $scopeId => $ids) {
                    $select = $this->downloadableLinkPrice->getQuery($ids, $scopeId);
                    $cursor = $this->resourceConnection->getConnection()->query($select);

                    while ($row = $cursor->fetch()) {
                        $result[$row['entity_id']][$scopeId] = $row['value'];
                    }
                }

                yield $this->getEventsData($indexDataChunk, $result);
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve downloadable link price data.');
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
     * @throws LocalizedException
     * @throws \InvalidArgumentException
     */
    private function getEventsData(array $indexData, array $actualData): array
    {
        $events = [];

        foreach ($indexData as $data) {
            $value = $actualData[$data['entity_id']][$data['scope_id']] ?? null;
            $eventType = null === $value ? self::EVENT_DOWNLOADABLE_LINK_PRICE_DELETED :
                self::EVENT_DOWNLOADABLE_LINK_PRICE_CHANGED;
            $websiteId = (string)$this->storeManager->getStore($data['scope_id'])->getWebsiteId();
            $key = $this->eventKeyGenerator->generate($eventType, $websiteId, null);
            $events[$key][] = $this->buildEventData($data['entity_id'], $value);
        }

        return $events;
    }

    /**
     * Build event data.
     *
     * @param string $entityId
     * @param string|null $value
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function buildEventData(string $entityId, ?string $value): array
    {
        $id = $this->downloadableLinksOptionUid->resolve([DownloadableLinksOptionUid::OPTION_ID => $entityId]);

        return [
            'id' => $id,
            'value' => $value,
        ];
    }
}
