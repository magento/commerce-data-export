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

use DateTime;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\Batch\FeedSource\Generator as FeedSourceBatchGenerator;
use Magento\DataExporter\Model\FailedItemsRegistry;
use Magento\DataExporter\Model\FeedExportStatusBuilder;
use Magento\DataExporter\Model\Indexer\Config as IndexerConfig;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Export\Processor as ExportProcessor;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\DataExporter\Model\FeedHashBuilder;
use Magento\Indexer\Model\ProcessManagerFactory;
use Throwable;
use Zend_Db_Statement_Exception;
use function array_chunk;
use function array_key_exists;
use function array_keys;
use function in_array;

/**
 * Base implementation of feed indexing behaviour, does not care about deleted entities
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FeedIndexProcessorCreateUpdate implements FeedIndexProcessorInterface
{
    private ResourceConnection $resourceConnection;
    private ExportProcessor $exportProcessor;
    private CommerceDataExportLoggerInterface $logger;
    private ExportFeedInterface $exportFeedProcessor;
    private FeedUpdater $feedUpdater;
    private FeedHashBuilder $hashBuilder;
    private SerializerInterface $serializer;
    private DeletedEntitiesProviderInterface $deletedEntitiesProvider;
    private ProcessManagerFactory $processManagerFactory;
    private BatchGeneratorInterface $batchGenerator;
    private IndexStateProviderFactory $indexStateProviderFactory;
    private FailedItemsRegistry $failedItemsRegistry;
    private ?FeedExportStatusBuilder $feedExportStatusBuilder;
    private IndexerConfig $indexerConfig;
    private bool $errorOccurredOnFullReindex = false;

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
     * @param ?FeedExportStatusBuilder $feedExportStatusBuilder
     * @param ?IndexerConfig $indexerConfig
     * @param ?FailedItemsRegistry $failedItemsRegistry
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
        ?IndexStateProviderFactory $indexStateProviderFactory = null,
        ?FeedExportStatusBuilder $feedExportStatusBuilder = null,
        ?IndexerConfig $indexerConfig = null,
        ?FailedItemsRegistry $failedItemsRegistry = null
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
        $this->feedExportStatusBuilder = $feedExportStatusBuilder ??
            ObjectManager::getInstance()->get(FeedExportStatusBuilder::class);
        $this->indexerConfig = $indexerConfig ??
            ObjectManager::getInstance()->get(IndexerConfig::class);
        $this->failedItemsRegistry = $failedItemsRegistry ??
            ObjectManager::getInstance()->get(FailedItemsRegistry::class);
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
     * @throws Zend_Db_Statement_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function partialReindex(
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        EntityIdsProviderInterface $idsProvider,
        array $ids = [],
        ?callable $callback = null,
        ?IndexStateProvider $indexState = null
    ): void {
        $isPartialReindex = $indexState === null;
        if ($isPartialReindex) {
            $indexState = $this->indexStateProviderFactory->create(['metadata' => $metadata]);
        }

        $feedIdentity = $metadata->getFeedIdentity();
        $arguments = [];
        foreach ($idsProvider->getAffectedIds($metadata, $ids) as $id) {
            $arguments[] = [$feedIdentity => $id];
        }
        foreach (array_chunk($arguments, $metadata->getBatchSize()) as $chunk) {
            $exportStatus = null;
            if ($metadata->isExportImmediately()) {
                $chunkTimeStamp = (new DateTime())->format($metadata->getDbDateTimeFormat());
                $dataProcessorCallback = function ($feedItemsRaw) use (
                    $metadata,
                    $serializer,
                    $chunk,
                    $indexState
                ) {
                    $feedItemsRaw = $this->failedItemsRegistry->mergeFailedItemsWithFeed($feedItemsRaw, $metadata);

                    //for backward compatibility:
                    //allows to execute plugins on Process method when callbacks are in place
                    $feedItems = $this->exportProcessor->process($metadata->getFeedName(), $chunk, $feedItemsRaw);
                    $feedItems = $this->addHashesAndModifiedAt($feedItems, $metadata);
                    $this->processFeedItems($feedItems, $metadata, $indexState, $serializer);
                };
                try {
                    // "delete" handler must not be called if error happened during exporting phase
                    $this->exportProcessor->processWithCallback($metadata, $chunk, $dataProcessorCallback);
                    $this->handleDeletedItems(
                        array_column($chunk, $feedIdentity),
                        $indexState,
                        $metadata,
                        $serializer,
                        $chunkTimeStamp
                    );
                } catch (Throwable $e) {
                    if (!$e instanceof UnableRetrieveData) {
                        $this->logger->error(
                            sprintf(
                                'Error during full sync. Message: "%s". Skipped IDs: [%s]',
                                $e->getMessage(),
                                implode(',', array_column($chunk, $feedIdentity))
                            ),
                            ['exception' => $e]
                        );
                    }
                    // for partial reindex thrown exception to return un-processed IDs back to changelog
                    if ($isPartialReindex) {
                        throw $e;
                    }
                    // keep going during full re-sync, but mark fail indexer at the end for next re-process
                    $this->errorOccurredOnFullReindex = true;
                }
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
            $this->errorOccurredOnFullReindex = false;
            $this->truncateIndexTable($metadata);
            $batchIterator = $this->batchGenerator->generate($metadata);
            $threadCount = min($metadata->getThreadCount(), $batchIterator->count());
            $userFunctions = [];
            for ($threadNumber = 1; $threadNumber <= $threadCount; $threadNumber++) {
                $userFunctions[] = function () use ($batchIterator, $metadata, $serializer, $idsProvider) {
                    $indexState = $this->indexStateProviderFactory->create(['metadata' => $metadata]);
                    foreach ($batchIterator as $ids) {
                        $this->partialReindex($metadata, $serializer, $idsProvider, $ids, null, $indexState);
                        // track iteration completion
                        $this->logger->logProgress();
                    }
                    $this->exportFeedItemsAndLogStatus($indexState, $metadata, $serializer, true);
                };
            }
            $processManager = $this->processManagerFactory->create(['threadsCount' => $threadCount]);
            $processManager->execute($userFunctions);
        } catch (Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        } finally {
            // must be thrown in order to mark indexer as "invalid" for next re-run
            if ($this->errorOccurredOnFullReindex) {
                throw new UnableRetrieveData('Full resync failed. Check logs for details');
            }

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
            $data = $this->processFailedFeedItems($data, $metadata, $serializer);
            if (empty($data)) {
                continue;
            }
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
            $data = $this->processFailedFeedItems($data, $metadata, $serializer);
            if (!empty($data)) {
                $exportStatus = $this->exportFeedProcessor->export(
                    array_column($data, FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA),
                    $metadata
                );
                $this->feedUpdater->execute($data, $exportStatus, $metadata, $serializer);
            }
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
     * @throws Zend_Db_Statement_Exception
     */
    private function filterFeedItems(
        array $feedItems,
        FeedIndexMetadata $metadata,
        ?IndexStateProvider $indexStateProvider = null
    ) : array {
        if (empty($feedItems)) {
            return [[], []];
        }
        $connection = $this->resourceConnection->getConnection();
        $feedIdsValues =  array_keys($feedItems);

        $updates = [];
        $select = $connection->select()
            ->from(
                ['f' => $this->resourceConnection->getTableName($metadata->getFeedTableName())],
                [
                    FeedIndexMetadata::FEED_TABLE_FIELD_PK,
                    FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID,
                    FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID,
                    FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH,
                    FeedIndexMetadata::FEED_TABLE_FIELD_STATUS,
                ]
            )->where(sprintf('f.%s IN (?)', FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID), $feedIdsValues);

        $cursor = $connection->query($select);

        while ($row = $cursor->fetch()) {
            $identifier = $row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID];
            $feedHash = $row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH];

            $indexStateProvider?->addProcessedHash($feedHash);
            if (isset($feedItems[$identifier][FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH])) {
                if ($this->isSendNeeded(
                    $feedItems[$identifier][FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH],
                    $feedHash,
                    (int)$row[FeedIndexMetadata::FEED_TABLE_FIELD_STATUS]
                )) {
                    $sourceEntityId = $feedItems[$identifier][FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID]
                        ?? $row[FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID];

                    $feedItems[$identifier][FeedIndexMetadata::FEED_TABLE_FIELD_PK]
                        = $row[FeedIndexMetadata::FEED_TABLE_FIELD_PK];
                    // Set actual source entity id to the record or assign one if it was not set before
                    $feedItems[$identifier][FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID] = $sourceEntityId;
                    $feedItems[$identifier][FeedIndexMetadata::FEED_TABLE_FIELD_STATUS]
                        = $row[FeedIndexMetadata::FEED_TABLE_FIELD_STATUS];
                    $updates[] = $feedItems[$identifier];
                }
                unset($feedItems[$identifier]);
            }
        }
        return [$feedItems, $updates];
    }

    /**
     * Determines if data should be sent based on status and feed changes.
     *
     * @example
     * | Status | Feed Changed = Yes | Feed Changed = No  |
     * |--------|--------------------|--------------------|
     * | -1     | Send               | Send               |
     * | 0      | Send               | Send               |
     * | 1      | Send               | Do not send        |
     * | 200    | Send               | Do not send        |
     * | 400    | Send               | Do not send        |
     * | 500    | Send               | Send               |
     * | 403    | Send               | Send               |
     *
     * @param string $oldFeedHash
     * @param string $newFeedHash
     * @param int $feedStatus
     * @return bool
     */
    private function isSendNeeded(string $oldFeedHash, string $newFeedHash, int $feedStatus): bool
    {
        return $oldFeedHash !== $newFeedHash
        || (
            !in_array(
                $feedStatus,
                ExportStatusCodeProvider::NON_RETRYABLE_HTTP_STATUS_CODE,
                true
            )
            && $feedStatus !== ExportStatusCodeProvider::FAILED_ITEM_ERROR
        )
        || $this->indexerConfig->includeSubmittedInDryRun();
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
                if (!array_key_exists('deleted', $row)) {
                    $row['deleted'] = false;
                }
                $identifier = $this->hashBuilder->buildIdentifierFromFeedItem($row, $metadata);
            }
            //Assign updated modifiedAt value to the record if it's required for feed
            if (in_array('modifiedAt', $metadata->getMinimalPayloadFieldsList(), true)) {
                $row['modifiedAt'] = (new DateTime())->format($metadata->getDateTimeFormat());
            }
            unset($data[$key]);
            if (empty($identifier)) {
                $this->logger->error(
                    'Identifier for feed item is empty. Skip sync for entity',
                    [
                        'feed' => $metadata->getFeedName(),
                        'item' => var_export($row, true),
                    ]
                );
                continue;
            }
            $hash = $this->hashBuilder->buildHash($row, $metadata);
            if (isset($row['errors'])) {
                $errors = $row['errors'];
                unset($row['errors']);
            } else {
                $errors = [];
            }
            $data[$identifier] = [
                // source entity id required only to persist data to table, but we still may send feed item
                FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID => $row[$metadata->getFeedIdentity()] ?? null,
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID => $identifier,
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH => $hash,
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA => $row,
                FeedIndexMetadata::FEED_TABLE_FIELD_ERRORS => $errors,
                FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED => $deleted,
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
     * @throws Zend_Db_Statement_Exception
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
     * @throws Zend_Db_Statement_Exception
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

    /**
     * Process failed feed items if there are any.
     *
     * Remove them from the main feed items array and return the rest.
     *
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @return array
     */
    private function processFailedFeedItems(
        array $data,
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer
    ): array {
        $iterationFailedItems = [];
        foreach ($data as $id => $item) {
            if (!empty($item['errors'])) {
                $iterationFailedItems[$item['errors']][] = $item;
                unset($data[$id]);
            }
        }
        foreach ($iterationFailedItems as $itemsError => $failedItems) {
            $exportStatus = $this->feedExportStatusBuilder->build(
                ExportStatusCodeProvider::FAILED_ITEM_ERROR,
                $itemsError
            );
            $this->feedUpdater->execute($failedItems, $exportStatus, $metadata, $serializer);
        }

        return $data;
    }
}
