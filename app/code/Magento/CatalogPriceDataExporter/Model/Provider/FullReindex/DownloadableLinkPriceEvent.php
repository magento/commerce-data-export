<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\FullReindex;

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
class DownloadableLinkPriceEvent implements FullReindexPriceProviderInterface
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
    public function retrieve(): \Generator
    {
        try {
            $queryArguments = $this->buildQueryArguments();

            foreach ($queryArguments as $scopeId => $ids) {
                $continue = true;
                $lastKnownId = 0;
                while ($continue === true) {
                    $result = [];
                    $select = $this->downloadableLinkPrice->getQuery($ids, $scopeId, (int)$lastKnownId, self::BATCH_SIZE);
                    $cursor = $this->resourceConnection->getConnection()->query($select);
                    while ($row = $cursor->fetch()) {
                        $result[$row['entity_id']][$scopeId] = $row['value'];
                        $lastKnownId = $row['link_id'];
                    }
                    if (empty($result)) {
                        $continue = false;
                    } else {
                        yield $this->getEventsData($result);
                    }
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve downloadable link price data.');
        }
    }

    /**
     * Build query arguments from index data or no data in case of full sync
     * todo: remove this function
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
     * Retrieve prices event data
     *
     * @param array $actualData
     *
     * @return array
     *
     * @throws LocalizedException
     * @throws \InvalidArgumentException
     */
    private function getEventsData(array $actualData): array
    {
        $events = [];
        foreach ($actualData as $entityId => $data) {
            foreach ($data as $scopeId => $value) {
                $websiteId = (string)$this->storeManager->getStore($scopeId)->getWebsiteId();
                $key = $this->eventKeyGenerator->generate(
                    self::EVENT_DOWNLOADABLE_LINK_PRICE_CHANGED,
                    $websiteId,
                    null
                );
                $events[$key][] = $this->buildEventData((string)$entityId, $value);
            }
        }
        return $events;
    }

    /**
     * Build event data.
     *
     * @param string $entityId
     * @param string $value
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function buildEventData(string $entityId, string $value): array
    {
        $id = $this->downloadableLinksOptionUid->resolve([DownloadableLinksOptionUid::OPTION_ID => $entityId]);

        return [
            'id' => $id,
            'value' => $value,
        ];
    }
}
