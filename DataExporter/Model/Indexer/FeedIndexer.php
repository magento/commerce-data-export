<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Export\Processor;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;

/**
 * Product export feed indexer class
 */
class FeedIndexer implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var FeedIndexMetadata
     */
    protected $feedIndexMetadata;

    /**
     * @var DataSerializerInterface
     */
    protected $dataSerializer;

    /**
     * @var FeedPool
     */
    protected $feedPool;

    /**
     * @var MarkRemovedEntitiesInterface
     */
    private $markRemovedEntities;

    /**
     * @var EntityIdsProviderInterface
     */
    private $entityIdsProvider;

    /**
     * @param Processor $processor
     * @param ResourceConnection $resourceConnection
     * @param DataSerializerInterface $serializer
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param FeedPool $feedPool
     * @param MarkRemovedEntitiesInterface $markRemovedEntities
     * @param EntityIdsProviderInterface $entityIdsProvider
     */
    public function __construct(
        Processor $processor,
        ResourceConnection $resourceConnection,
        DataSerializerInterface $serializer,
        FeedIndexMetadata $feedIndexMetadata,
        FeedPool $feedPool,
        MarkRemovedEntitiesInterface $markRemovedEntities,
        EntityIdsProviderInterface $entityIdsProvider
    ) {
        $this->processor = $processor;
        $this->resourceConnection = $resourceConnection;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->dataSerializer = $serializer;
        $this->feedPool = $feedPool;
        $this->markRemovedEntities = $markRemovedEntities;
        $this->entityIdsProvider = $entityIdsProvider;
    }

    /**
     * Execute full indexation
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function executeFull()
    {
        $this->truncateFeedTable();
        foreach ($this->entityIdsProvider->getAllIds($this->feedIndexMetadata) as $batch) {
            $ids = \array_column($batch, $this->feedIndexMetadata->getFeedIdentity());
            $this->markRemovedEntities->execute(
                \array_column($ids, $this->feedIndexMetadata->getFeedIdentity()),
                $this->feedIndexMetadata
            );
            $this->process($ids);
        }
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->markRemovedEntities->execute($ids, $this->feedIndexMetadata);
        $this->process($ids);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id)
    {
        $this->markRemovedEntities->execute([$id], $this->feedIndexMetadata);
        $this->process([$id]);
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     * @api
     */
    public function execute($ids)
    {
        $this->markRemovedEntities->execute($ids, $this->feedIndexMetadata);
        $this->process($ids);
    }

    /**
     * Indexer feed data processor
     *
     * @param array $ids
     *
     * @return void
     */
    private function process(array $ids = []): void
    {
        $feedIdentity = $this->feedIndexMetadata->getFeedIdentity();
        $arguments = [];
        foreach ($this->entityIdsProvider->getAffectedIds($this->feedIndexMetadata, $ids) as $id) {
            $arguments[] = [$feedIdentity => $id];
        }
        $data = $this->processor->process($this->feedIndexMetadata->getFeedName(), $arguments);
        $chunks = array_chunk($data, $this->feedIndexMetadata->getBatchSize());
        $connection = $this->resourceConnection->getConnection();
        foreach ($chunks as $chunk) {
            $connection->insertOnDuplicate(
                $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName()),
                $this->dataSerializer->serialize($chunk),
                $this->feedIndexMetadata->getFeedTableMutableColumns()
            );
        }
    }

    /**
     * Truncate feed table
     *
     * @return void
     */
    protected function truncateFeedTable(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $feedTable = $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName());
        $connection->truncateTable($feedTable);
    }
}
