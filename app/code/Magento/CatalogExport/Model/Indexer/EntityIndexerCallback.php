<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Model\Indexer;

use Magento\CatalogExport\Model\ChangedEntitiesMessageBuilder;
use Magento\DataExporter\Model\FeedPool;
use Magento\DataExporter\Model\Indexer\FeedIndexerCallbackInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Publishes data of updated entities in queue
 */
class EntityIndexerCallback implements FeedIndexerCallbackInterface
{
    /**
     * @var PublisherInterface
     */
    private $queuePublisher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ChangedEntitiesMessageBuilder
     */
    private $messageBuilder;

    /**
     * @var FeedPool
     */
    private $feedPool;

    /**
     * @var FeedIndexMetadata
     */
    private $feedIndexMetadata;

    /**
     * @var string
     */
    private $topicName;

    /**
     * @var string
     */
    private $updatedEventType;

    /**
     * @var string
     */
    private $deletedEventType;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @param PublisherInterface $queuePublisher
     * @param ChangedEntitiesMessageBuilder $messageBuilder
     * @param FeedPool $feedPool
     * @param LoggerInterface $logger
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param string $topicName
     * @param string $updatedEventType
     * @param string $deletedEventType
     * @param int $batchSize
     */
    public function __construct(
        PublisherInterface $queuePublisher,
        ChangedEntitiesMessageBuilder $messageBuilder,
        FeedPool $feedPool,
        LoggerInterface $logger,
        FeedIndexMetadata $feedIndexMetadata,
        string $topicName,
        string $updatedEventType,
        string $deletedEventType,
        int $batchSize = 100
    ) {
        $this->queuePublisher = $queuePublisher;
        $this->messageBuilder = $messageBuilder;
        $this->feedPool = $feedPool;
        $this->logger = $logger;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->topicName = $topicName;
        $this->updatedEventType = $updatedEventType;
        $this->deletedEventType = $deletedEventType;
        $this->batchSize = $batchSize;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $entityData, array $deleteIds) : void
    {
        foreach ($this->getDeleteEntitiesData($deleteIds) as $storeCode => $entities) {
            foreach (\array_chunk($entities, $this->batchSize) as $chunk) {
                $this->publishMessage(
                    $this->deletedEventType,
                    $chunk,
                    $storeCode
                );
            }
        }

        foreach ($this->getUpdateEntitiesData($entityData) as $storeCode => $entities) {
            foreach (\array_chunk($entities, $this->batchSize) as $chunk) {
                $this->publishMessage(
                    $this->updatedEventType,
                    $chunk,
                    $storeCode
                );
            }
        }
    }

    /**
     * Get deleted entities data
     *
     * @param array $deleteIds
     *
     * @return array
     */
    private function getDeleteEntitiesData(array $deleteIds): array
    {
        $deleted = [];
        $feed = $this->feedPool->getFeed($this->feedIndexMetadata->getFeedName());
        foreach ($feed->getDeletedByIds($deleteIds) as $entity) {
            $deleted[$entity['storeViewCode'] ?? null][] = [
                'entity_id' => $entity[$this->feedIndexMetadata->getFeedIdentity()],
            ];
        }

        return $deleted;
    }

    /**
     * Get update entities data
     *
     * @param array $entityData
     *
     * @return array
     */
    private function getUpdateEntitiesData(array $entityData): array
    {
        $entitiesArray = [];
        foreach ($entityData as $entity) {
            $entitiesArray[$entity['storeViewCode'] ?? null][] = [
                'entity_id' => $entity[$this->feedIndexMetadata->getFeedIdentity()],
                'attributes' => $entity['attributes'] ?? [],
            ];
        }

        return $entitiesArray;
    }

    /**
     * Publish deleted or updated message
     *
     * @param string $eventType
     * @param array $entities
     * @param string $scope
     *
     * @return void
     */
    private function publishMessage(string $eventType, array $entities, string $scope): void
    {
        $message = $this->messageBuilder->build($eventType, $entities, $scope ?: null);

        try {
            $this->queuePublisher->publish($this->topicName, $message);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'topic "%s": error on publish message "%s"',
                    $this->topicName,
                    \json_encode(['type' => $eventType, 'entities' => $entities, 'scope' => $scope])
                ),
                ['exception' => $e]
            );
        }
    }
}
