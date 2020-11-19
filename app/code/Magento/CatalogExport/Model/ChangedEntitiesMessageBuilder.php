<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Model;

use Magento\CatalogExport\Event\Data\ChangedEntities;
use Magento\CatalogExport\Event\Data\ChangedEntitiesFactory;
use Magento\CatalogExport\Event\Data\DataFactory;
use Magento\CatalogExport\Event\Data\MetaFactory;
use Magento\CatalogExport\Event\Data\EntityFactory;

/**
 * Class that builds queue message for changed entities
 */
class ChangedEntitiesMessageBuilder
{
    /**
     * @var ChangedEntitiesFactory
     */
    private $changedEntitiesFactory;

    /**
     * @var MetaFactory
     */
    private $metaFactory;

    /**
     * @var DataFactory
     */
    private $dataFactory;

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @param ChangedEntitiesFactory $changedEntitiesFactory
     * @param MetaFactory $metaFactory
     * @param DataFactory $dataFactory
     * @param EntityFactory $entityFactory
     */
    public function __construct(
        ChangedEntitiesFactory $changedEntitiesFactory,
        MetaFactory $metaFactory,
        DataFactory $dataFactory,
        EntityFactory $entityFactory
    ) {
        $this->changedEntitiesFactory = $changedEntitiesFactory;
        $this->metaFactory = $metaFactory;
        $this->dataFactory = $dataFactory;
        $this->entityFactory = $entityFactory;
    }

    /**
     * Build message object
     *
     * @param string $eventType
     * @param array $entities
     * @param string|null $scope
     *
     * @return \Magento\CatalogExport\Event\Data\ChangedEntities
     */
    public function build(string $eventType, array $entities, ?string $scope = null): ChangedEntities
    {
        $meta = $this->metaFactory->create();
        $meta->setScope($scope);
        $meta->setEventType($eventType);

        $entitiesArray = [];
        foreach ($entities as $entityData) {
            $entity = $this->entityFactory->create();
            $entity->setEntityId((string)$entityData['entity_id']);
            $entity->setAttributes($entityData['attributes'] ?? []);

            $entitiesArray[] = $entity;
        }

        $data = $this->dataFactory->create();
        $data->setEntities($entitiesArray);

        return $this->changedEntitiesFactory->create(
            [
                'meta' => $meta,
                'data' => $data
            ]
        );
    }
}
