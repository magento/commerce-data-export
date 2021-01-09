<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\PartialReindex;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\ComplexProductLink;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing complex product variation change events
 */
class ComplexProductEvent implements PartialReindexPriceProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ComplexProductLink
     */
    private $complexProductLink;

    /**
     * @var EventKeyGenerator
     */
    private $eventKeyGenerator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $linkType;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ComplexProductLink $complexProductLink
     * @param EventKeyGenerator $eventKeyGenerator
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param string $linkType
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ComplexProductLink $complexProductLink,
        EventKeyGenerator $eventKeyGenerator,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        string $linkType
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->complexProductLink = $complexProductLink;
        $this->eventKeyGenerator = $eventKeyGenerator;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->linkType = $linkType;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(array $indexData): \Generator
    {
        try {
            foreach (\array_chunk($indexData, self::BATCH_SIZE) as $indexDataChunk) {
                $result = [];
                $parentIds = [];
                $variationIds = [];

                foreach ($indexDataChunk as $key => $data) {
                    if (null === $data['parent_id']) {
                        unset($indexDataChunk[$key]);
                        continue;
                    }

                    $parentIds[] = $data['parent_id'];
                    $variationIds[] = $data['entity_id'];
                }

                $select = $this->complexProductLink->getQuery($parentIds, $variationIds);
                $cursor = $this->resourceConnection->getConnection()->query($select);

                while ($row = $cursor->fetch()) {
                    $result[$row['parent_id']][] = $row['variation_id'];
                }

                yield $this->getEventData($indexDataChunk, $result);
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve complex product link data.');
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
     */
    private function getEventData(array $indexData, array $actualData): array
    {
        $events = [];

        foreach ($indexData as $data) {
            $actualVariations = $actualData[$data['parent_id']] ?? [];
            $eventType = \in_array($data['entity_id'], $actualVariations) ? self::EVENT_VARIATION_CHANGED
                : self::EVENT_VARIATION_DELETED;
            $websiteId = (string)$this->storeManager->getWebsite(WebsiteInterface::ADMIN_CODE)->getWebsiteId();
            $key = $this->eventKeyGenerator->generate($eventType, $websiteId, null);
            $events[$key][] = $this->buildEventData($data['parent_id'], $data['entity_id']);
        }

        return $events;
    }

    /**
     * Build event data.
     *
     * @param string $parentId
     * @param string $variationId
     *
     * @return array
     */
    private function buildEventData(string $parentId, string $variationId): array
    {
        return [
            'id' => $parentId,
            'variation_id' => $variationId,
            'price_type' => $this->linkType,
        ];
    }
}
