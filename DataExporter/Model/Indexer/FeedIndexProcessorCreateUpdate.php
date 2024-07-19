<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\Batch\FeedSource\Generator as FeedSourceBatchGenerator;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Export\Processor as ExportProcessor;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\DataExporter\Model\FeedHashBuilder;
use Magento\Indexer\Model\ProcessManagerFactory;

/**
 * Base implementation of feed indexing behaviour, does not care about deleted entities
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FeedIndexProcessorCreateUpdate implements FeedIndexProcessorInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var ExportProcessor
     */
    private ExportProcessor $exportProcessor;

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @var ExportFeedInterface
     */
    private $exportFeedProcessor;

    /**
     * @var FeedUpdater
     */
    private FeedUpdater $feedUpdater;

    /**
     * @var FeedHashBuilder
     */
    private FeedHashBuilder $hashBuilder;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var DeletedEntitiesProviderInterface
     */
    private DeletedEntitiesProviderInterface $deletedEntitiesProvider;

    /**
     * @var ProcessManagerFactory
     */
    private ProcessManagerFactory $processManagerFactory;

    /**
     * @var BatchGeneratorInterface
     */
    private BatchGeneratorInterface $batchGenerator;

    /**
     * @var IndexStateProviderFactory
     */
    private IndexStateProviderFactory $indexStateProviderFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ExportProcessor $exportProcessor
     * @param ExportFeedInterface $exportFeedProcessor
     * @param FeedUpdater $feedUpdater
     * @param FeedHashBuilder $hashBuilder
     * @param SerializerInterface $serializer
     * @param CommerceDataExportLoggerInterface $logger
     * @param DeletedEntitiesProviderInterface|null $deletedEntitiesProvider
     * @param ProcessManagerFactory|null $processManagerFactory
     * @param BatchGeneratorInterface|null $batchGenerator
     * @param ?IndexStateProviderFactory $indexStateProviderFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ExportProcessor $exportProcessor,
        ExportFeedInterface $exportFeedProcessor,
        FeedUpdater $feedUpdater,
        FeedHashBuilder $hashBuilder,
        SerializerInterface $serializer,
        CommerceDataExportLoggerInterface $logger,
        ?DeletedEntitiesProviderInterface $deletedEntitiesProvider = null,
        ?ProcessManagerFactory $processManagerFactory = null,
        ?BatchGeneratorInterface $batchGenerator = null,
        ?IndexStateProviderFactory $indexStateProviderFactory = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->exportProcessor = $exportProcessor;
        $this->exportFeedProcessor = $exportFeedProcessor;
        $this->feedUpdater = $feedUpdater;
        $this->hashBuilder = $hashBuilder;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->deletedEntitiesProvider = $deletedEntitiesProvider ??
            ObjectManager::getInstance()->get(DeletedEntitiesProviderInterface::class);
        $this->processManagerFactory = $processManagerFactory ??
            ObjectManager::getInstance()->get(ProcessManagerFactory::class);
        $this->batchGenerator = $batchGenerator ??
            ObjectManager::getInstance()->get(FeedSourceBatchGenerator::class);
        $this->indexStateProviderFactory = $indexStateProviderFactory ??
            ObjectManager::getInstance()->get(IndexStateProviderFactory::class);
    }

    /**
     * {@inerhitDoc}
     *
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param EntityIdsProviderInterface $idsProvider
     * @param array $ids
     * @param callable|null $callback
     * @param IndexStateProvider|null $indexState
     * @throws \Zend_Db_Statement_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function partialReindex(
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        EntityIdsProviderInterface $idsProvider,
        array $ids = [],
        callable $callback = null,
        IndexStateProvider $indexState = null
    ): void {
        $isPartialReindex = $indexState === null;
        if ($isPartialReindex) {
            $indexState = $this->indexStateProviderFactory->create(['batchSize' => $metadata->getBatchSize()]);
        }

        $feedIdentity = $metadata->getFeedIdentity();
        $arguments = [];
        foreach ($idsProvider->getAffectedIds($metadata, $ids) as $id) {
            $arguments[] = [$feedIdentity => $id];
        }
        foreach (\array_chunk($arguments, $metadata->getBatchSize()) as $chunk) {
            $exportStatus = null;
            if ($metadata->isExportImmediately()) {
                $chunkTimeStamp = (new \DateTime())->format($metadata->getDbDateTimeFormat());
                $dataProcessorCallback = function ($feedItems) use (
                    $metadata,
                    $serializer,
                    $chunk,
                    $indexState
                ) {
                    //for backward compatibility:
                    //allows to execute plugins on Process method when callbacks are in place
                    $feedItems = $this->exportProcessor->process($metadata->getFeedName(), $chunk, $feedItems);
                    $feedItems = $this->addHashesAndModifiedAt($feedItems, $metadata);
                    $this->processFeedItems($feedItems, $metadata, $indexState, $serializer);
                };
                $this->exportProcessor->processWithCallback($metadata, $chunk, $dataProcessorCallback);

                $this->handleDeletedItems(
                    array_column($chunk, $feedIdentity),
                    $indexState,
                    $metadata,
                    $serializer,
                    $chunkTimeStamp
                );
            } else {
                $this->feedUpdater->execute(
                    $this->exportProcessor->process($metadata->getFeedName(), $chunk),
                    $exportStatus,
                    $metadata,
                    $serializer
                );
            }
        }

        if ($isPartialReindex) {
            $this->exportFeedItemsAndLogStatus($indexState, $metadata, $serializer, true);
        }
        $this->logger->logProgress(count($ids));
    }

    /**
     * @inheritDoc
     */
    public function fullReindex(
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        EntityIdsProviderInterface $idsProvider
    ): void {
        try {
            $this->truncateIndexTable($metadata);
            $batchIterator = $this->batchGenerator->generate($metadata);
            $threadCount = min($metadata->getThreadCount(), $batchIterator->count());
            $userFunctions = [];
            for ($threadNumber = 1; $threadNumber <= $threadCount; $threadNumber++) {
                $userFunctions[] = function () use ($batchIterator, $metadata, $serializer, $idsProvider) {
                    $indexState = $this->indexStateProviderFactory->create(['batchSize' => $metadata->getBatchSize()]);
                    try {
                        foreach ($batchIterator as $ids) {
                            $this->partialReindex($metadata, $serializer, $idsProvider, $ids, null, $indexState);
                            // track iteration completion
                            $this->logger->logProgress();
                        }
                        $this->exportFeedItemsAndLogStatus($indexState, $metadata, $serializer, true);
                    } catch (\Throwable $e) {
                        $this->logger->error(
                            'Data Exporter exception has occurred: ' . $e->getMessage(),
                            ['exception' => $e]
                        );
                        throw $e;
                    }
                };
            }
            $processManager = $this->processManagerFactory->create(['threadsCount' => $threadCount]);
            $processManager->execute($userFunctions);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Export feed items and log status.
     *
     * @param IndexStateProvider $indexStateProvider
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param bool $processRemainingFeedItems
     * @return void
     */
    private function exportFeedItemsAndLogStatus(
        IndexStateProvider $indexStateProvider,
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        bool $processRemainingFeedItems = false
    ) {
        while ($indexStateProvider->isBatchLimitReached()) {
            $data = $indexStateProvider->getFeedItems();
            $exportStatus = $this->exportFeedProcessor->export(
                array_column($data, FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA),
                $metadata
            );
            $this->feedUpdater->execute($data, $exportStatus, $metadata, $serializer);
        }

        if ($processRemainingFeedItems) {
            $data = $indexStateProvider->getFeedItems();
            if (!$data) {
                return ;
            }
            $exportStatus = $this->exportFeedProcessor->export(
                array_column($data, FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA),
                $metadata
            );
            $this->feedUpdater->execute($data, $exportStatus, $metadata, $serializer);
        }
    }

    /**
     * Truncates index table
     *
     * @param FeedIndexMetadata $metadata
     */
    private function truncateIndexTable(FeedIndexMetadata $metadata): void
    {
        if (!$metadata->isTruncateFeedOnFullReindex() || $metadata->isExportImmediately()) {
            return ;
        }
        $connection = $this->resourceConnection->getConnection();
        $feedTable = $this->resourceConnection->getTableName($metadata->getFeedTableName());
        $connection->truncateTable($feedTable);
    }

    /**
     * Remove feed items from further processing if all true:
     * - item hash didn't change
     * - previous export status is non-retryable
     *
     * @param array $feedItems
     * @param FeedIndexMetadata $metadata
     * @param null|IndexStateProvider $indexStateProvider
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function filterFeedItems(
        array $feedItems,
        FeedIndexMetadata $metadata,
        IndexStateProvider $indexStateProvider = null
    ) : array {
        if (empty($feedItems)) {
            return [[], []];
        }
        $connection = $this->resourceConnection->getConnection();
        $feedIdsValues =  \array_keys($feedItems);

        $updates = [];
        $select = $connection->select()
            ->from(
                ['f' => $this->resourceConnection->getTableName($metadata->getFeedTableName())],
                [
                    FeedIndexMetadata::FEED_TABLE_FIELD_PK,
                    FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID,
                    FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH,
                    FeedIndexMetadata::FEED_TABLE_FIELD_STATUS
                ]
            )->where(sprintf('f.%s IN (?)', FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID), $feedIdsValues);

        $cursor = $connection->query($select);

        while ($row = $cursor->fetch()) {
            $identifier = $row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID];
            $feedHash = $row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH];

            if ($indexStateProvider !== null) {
                $indexStateProvider->addProcessedHash($feedHash);
            }
            if (isset($feedItems[$identifier][FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH])) {
                if (\in_array(
                    (int)$row[FeedIndexMetadata::FEED_TABLE_FIELD_STATUS],
                    ExportStatusCodeProvider::NON_RETRYABLE_HTTP_STATUS_CODE,
                    true
                    )
                    && $feedHash == $feedItems[$identifier][FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH]
                ) {
                    unset($feedItems[$identifier]);
                } else {
                    $feedItems[$identifier][FeedIndexMetadata::FEED_TABLE_FIELD_PK]
                        = $row[FeedIndexMetadata::FEED_TABLE_FIELD_PK];
                    $updates[] = $feedItems[$identifier];
                    unset($feedItems[$identifier]);
                }
            }
        }
        return [$feedItems, $updates];
    }

    /**
     * Add hashes and modifiedAt field if it's required
     *
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @param bool $deleted
     * @return array
     */
    private function addHashesAndModifiedAt(array $data, FeedIndexMetadata $metadata, bool $deleted = false): array
    {
        foreach ($data as $key => $row) {
            if ($deleted) {
                if (!isset($row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH])) {
                    $this->logger->error("Feed hash is not set for the product id: ". $row['productId']);
                    unset($data[$key]);
                    continue ;
                }
                $identifier = $row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID];
                $row = $this->serializer->unserialize($row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA]);
                $row['deleted'] = true;
            } else {
                if (!\array_key_exists('deleted', $row)) {
                    $row['deleted'] = false;
                }
                $identifier = $this->hashBuilder->buildIdentifierFromFeedItem($row, $metadata);
            }
            //Assign updated modifiedAt value to the record if it's required for feed
            if (\in_array('modifiedAt', $metadata->getMinimalPayloadFieldsList(), true)) {
                $row['modifiedAt'] = (new \DateTime())->format($metadata->getDateTimeFormat());
            }
            unset($data[$key]);
            if (empty($identifier)) {
                $this->logger->error(
                    'Identifier for feed item is empty. Skip sync for entity',
                    [
                        'feed' => $metadata->getFeedName(),
                        'item' => var_export($row, true)
                    ]
                );
                continue;
            }
            $hash = $this->hashBuilder->buildHash($row, $metadata);
            $data[$identifier] = [
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID => $identifier,
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH => $hash,
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA => $row,
                FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED => $deleted
            ];
        }
        return $data;
    }

    /**
     * Algorithm to mark feed items deleted:
     * - select all feed items for <$ids> where modifiedAt < "currentModifiedAt" - e.g. product
     * - remove hashes that were already processed
     * - mark entity as "deleted"
     *
     * @param array $ids
     * @param IndexStateProvider $indexStateProvider
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param string $recentTimeStamp
     * @throws \Zend_Db_Statement_Exception
     */
    private function handleDeletedItems(
        array                   $ids,
        IndexStateProvider      $indexStateProvider,
        FeedIndexMetadata       $metadata,
        DataSerializerInterface $serializer,
        string $recentTimeStamp
    ): void {
        foreach ($this->deletedEntitiesProvider->get(
            $ids,
            $indexStateProvider->getProcessedHashes(),
            $metadata,
            $recentTimeStamp
        ) as $feedItems) {
            $feedItems = $this->addHashesAndModifiedAt($feedItems, $metadata, true);
            $this->processFeedItems($feedItems, $metadata, $indexStateProvider, $serializer);
        }
    }

    /**
     * Process feed items
     *
     * @param array $feedItems
     * @param FeedIndexMetadata $metadata
     * @param IndexStateProvider|null $indexState
     * @param DataSerializerInterface $serializer
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function processFeedItems(
        array $feedItems,
        FeedIndexMetadata $metadata,
        ?IndexStateProvider $indexState,
        DataSerializerInterface $serializer
    ): void {
        [$inserts, $updates] = $this->filterFeedItems($feedItems, $metadata, $indexState);
        if (empty($inserts) && empty($updates)) {
            return;
        }
        if ($inserts) {
            $indexState->addItems($inserts);
        }
        if ($updates) {
            $indexState->addUpdates($updates);
        }
        if ($indexState->isBatchLimitReached()) {
            $this->exportFeedItemsAndLogStatus($indexState, $metadata, $serializer);
        }
    }
}
