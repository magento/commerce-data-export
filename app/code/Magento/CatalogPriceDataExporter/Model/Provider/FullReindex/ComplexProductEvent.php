<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Provider\FullReindex;

use Magento\CatalogPriceDataExporter\Model\EventKeyGenerator;
use Magento\CatalogPriceDataExporter\Model\Query\ComplexProductLink;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing complex product variation change events for full indexation
 */
class ComplexProductEvent implements FullReindexPriceProviderInterface
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
    public function retrieve(): \Generator
    {
        $continue = true;
        $lastKnownId = 0;
        try {
            while ($continue === true) {
                $result = [];
                $select = $this->complexProductLink->getQuery(null, null, (int)$lastKnownId, self::BATCH_SIZE);
                $cursor = $this->resourceConnection->getConnection()->query($select);
                while ($row = $cursor->fetch()) {
                    $result[$row['parent_id']][] = $row['variation_id'];
                    $lastKnownId = $row['link_id'];
                }
                if (empty($result)) {
                    $continue = false;
                } else {
                    yield $this->getEventsData($result);
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve complex product link data for full sync.');
        }
    }

    /**
     * Retrieve prices event data
     *
     * @param array $data
     *
     * @return array
     *
     * @throws LocalizedException
     */
    private function getEventsData(array $data): array
    {
        $events = [];
        $websiteId = (string)$this->storeManager->getWebsite(WebsiteInterface::ADMIN_CODE)->getWebsiteId();
        $key = $this->eventKeyGenerator->generate(self::EVENT_VARIATION_CHANGED, $websiteId, null);
        foreach ($data as $parentId => $children) {
            foreach ($children as $variationId) {
                $events[$key][] = $this->buildEventData((string)$parentId, $variationId);
            }
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
            'child_id' => $variationId,
            'price_type' => $this->linkType,
        ];
    }
}
