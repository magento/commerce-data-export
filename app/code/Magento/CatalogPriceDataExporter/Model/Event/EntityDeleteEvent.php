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
     * @var array|null
     */
    private $idProvider;

    /**
     * @param ResourceConnection $resourceConnection
     * @param EntityDelete $entityDelete
     * @param EventBuilder $eventBuilder
     * @param LoggerInterface $logger
     * @param string $eventType
     * @param array|null $idProvider
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        EntityDelete $entityDelete,
        EventBuilder $eventBuilder,
        LoggerInterface $logger,
        string $eventType,
        ?array $idProvider = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->entityDelete = $entityDelete;
        $this->eventBuilder = $eventBuilder;
        $this->logger = $logger;
        $this->eventType = $eventType;
        $this->idProvider = $idProvider;
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
                $queryArguments[] = $data['entity_id'];
            }

            $select = $this->entityDelete->getQuery($queryArguments);
            $cursor = $this->resourceConnection->getConnection()->query($select);

            while ($row = $cursor->fetch()) {
                $result[$row['entity_id']] = $row;
            }

            $events = $this->getEventsData($indexData, $result);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve entity delete event data.');
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
    private function getEventsData(array $indexData, array $actualData): array
    {
        $events = [];

        foreach ($indexData as $data) {
            if (!isset($actualData[$data['entity_id']])) {
                $events[] = $this->buildEventData($data);
            }
        }

        return $events;
    }

    /**
     * Build event data.
     *
     * @param array $indexData
     *
     * @return array
     */
    private function buildEventData(array $indexData): array
    {
        $id = $this->resolveId($indexData);
        return $this->eventBuilder->build(
            $this->eventType,
            $id,
            WebsiteInterface::ADMIN_CODE,
            null,
            null
        );
    }

    /**
     * Resolve price entity id
     *
     * @param array $indexData
     *
     * @return string
     */
    private function resolveId(array $indexData): string
    {
        if ($this->idProvider === null) {
            return base64_encode($indexData['entity_id']);
        }
        $parameters = [];
        foreach ($this->idProvider['parameters'] as $param) {
            $parameters[$param['parameterKey']] = $indexData[$param['valueKey']];
        }
        return $this->idProvider['class']->resolve($parameters);
    }
}
