<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\ComplexProductLink;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\WebsiteInterface;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for providing complex product variation change events
 */
class ComplexProductEvent implements ProductPriceEventInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var EventBuilder
     */
    private $eventBuilder;

    /**
     * @var ComplexProductLink
     */
    private $complexProductLink;

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
     * @param EventBuilder $eventBuilder
     * @param ComplexProductLink $complexProductLink
     * @param LoggerInterface $logger
     * @param string $linkType
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        EventBuilder $eventBuilder,
        ComplexProductLink $complexProductLink,
        LoggerInterface $logger,
        string $linkType
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eventBuilder = $eventBuilder;
        $this->complexProductLink = $complexProductLink;
        $this->logger = $logger;
        $this->linkType = $linkType;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(array $indexData): array
    {
        $result = [];
        $parentIds = [];
        $variationIds = [];

        try {
            foreach ($indexData as $key => $data) {
                if (null === $data['parent_id']) {
                    unset($indexData[$key]);
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

            $events = $this->getEventData($indexData, $result);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve complex product link data.');
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
     */
    private function getEventData(array $indexData, array $actualData): array
    {
        $events = [];

        foreach ($indexData as $data) {
            $actualVariations = $actualData[$data['parent_id']] ?? [];
            $event = \in_array($data['entity_id'], $actualVariations) ? self::EVENT_VARIATION_CHANGED
                : self::EVENT_VARIATION_DELETED;

            $events[] = $this->buildEventData($data['parent_id'], $data['entity_id'], $event);
        }

        return $events;
    }

    /**
     * Build event data.
     *
     * @param string $parentId
     * @param string $variationId
     * @param string $eventType
     *
     * @return array
     */
    private function buildEventData(string $parentId, string $variationId, string $eventType): array
    {
        $additionalData = [
            'meta' => ['price_type' => $this->linkType],
            'data' => ['variation_id' => $variationId]
        ];

        return $this->eventBuilder->build(
            $eventType,
            $parentId,
            WebsiteInterface::ADMIN_CODE,
            null,
            null,
            $additionalData
        );
    }
}
