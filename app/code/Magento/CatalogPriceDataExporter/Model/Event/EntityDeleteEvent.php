<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

use Magento\CatalogPriceDataExporter\Model\EventBuilder;
use Magento\CatalogPriceDataExporter\Model\Query\EntityDelete;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\WebsiteInterface;
use Psr\Log\LoggerInterface;

class EntityDeleteEvent implements ProductPriceEventInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var EntityDelete
     */
    private $entityDelete;

    /**
     * @var EventBuilder
     */
    private $eventBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $eventType;

    /**
     * @param ResourceConnection $resourceConnection
     * @param EntityDelete $entityDelete
     * @param EventBuilder $eventBuilder
     * @param LoggerInterface $logger
     * @param string $eventType
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        EntityDelete $entityDelete,
        EventBuilder $eventBuilder,
        LoggerInterface $logger,
        string $eventType
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->entityDelete = $entityDelete;
        $this->eventBuilder = $eventBuilder;
        $this->logger = $logger;
        $this->eventType = $eventType;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(array $indexData): array
    {
        $events = [];

        try {
            $select = $this->entityDelete->getQuery($indexData['entity_id']);
            $result = $this->resourceConnection->getConnection()->fetchOne($select);

            if (false === $result) {
                $events[] = $this->getEventData($indexData);
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve entity delete event data.');
        }

        return $events;
    }

    /**
     * Retrieve event data.
     *
     * @param array $indexData
     *
     * @return array
     */
    private function getEventData(array $indexData): array
    {
        return $this->eventBuilder->build(
            $this->eventType,
            $indexData['entity_id'], // TODO base64_encode with correct format
            WebsiteInterface::ADMIN_CODE,
            null,
            null
        );
    }
}
