<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\Batch\FeedSource\Generator as FeedSourceBatchGenerator;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Model\Logging\LogRegistry;
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
     * @var string
     */
    private const MODIFIED_AT_FORMAT = 'Y-m-d H:i:s';

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
     * @var array
     */
    private $feedTablePrimaryKey;

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
            $metadata->setCurrentModifiedAtTimeInDBFormat((new \DateTime())->format(self::MODIFIED_AT_FORMAT));
            $exportStatus = null;
            if ($metadata->isExportImmediately()) {
                $dataProcessorCallback = function ($feedItems) use (
                    $metadata,
                    $serializer,
                    $chunk,
                    $indexState
                ) {
                    //for backward compatibility:
                    //allows to execute plugins on Process method when callbacks are in place
                    $feedItems = $this->exportProcessor->process($metadata->getFeedName(), $chunk, $feedItems);
                    $feedItems = $this->addHashes($feedItems, $metadata);
                    $data = $this->filterFeedItems($feedItems, $metadata, $indexState);
                    if (empty($data)) {
                        return;
                    }
                    $indexState->addItems($data);

                    if ($indexState->isBatchLimitReached()) {
                        $this->exportFeedItemsAndLogStatus($indexState, $metadata, $serializer);
                    }
                };
                $this->exportProcessor->processWithCallback($metadata, $chunk, $dataProcessorCallback);

                $this->handleDeletedItems(
                    array_column($chunk, $feedIdentity),
                    $indexState,
                    $metadata,
                    $serializer
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
                array_column($data, 'feed'),
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
                array_column($data, 'feed'),
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
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $primaryKeyFields = $this->getFeedTablePrimaryKey($metadata);
        $primaryKeys = \array_keys($feedItems);
        $primaryKeys = count($primaryKeyFields) == 1
            ? \implode(',', $primaryKeys)
            : '(' . \implode('),(', $primaryKeys) . ')';

        $select = $connection->select()
            ->from(
                ['f' => $this->resourceConnection->getTableName($metadata->getFeedTableName())],
                array_merge($primaryKeyFields, ['feed_hash', 'status'])
            )->where(sprintf('(%s) IN (%s)', \implode(', ', $primaryKeyFields), $primaryKeys));

        $cursor = $connection->query($select);

        while ($row = $cursor->fetch()) {
            $identifier = $this->hashBuilder->buildIdentifierFromFeedTableRow($row, $metadata);
            $feedHash = $row['feed_hash'];

            if ($indexStateProvider !== null) {
                $indexStateProvider->addProcessedHash($feedHash);
            }
            if (\in_array((int)$row['status'], ExportStatusCodeProvider::NON_RETRYABLE_HTTP_STATUS_CODE, true)
                && isset($feedItems[$identifier]['hash'])
                && $feedHash == $feedItems[$identifier]['hash']) {
                unset($feedItems[$identifier]);
            }
        }
        return $feedItems;
    }

    /**
     * Add hashes
     *
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @param bool $deleted
     * @return array
     */
    private function addHashes(array $data, FeedIndexMetadata $metadata, bool $deleted = false): array
    {
        foreach ($data as $key => $row) {
            if ($deleted) {
                if (!isset($row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH])) {
                    $this->logger->error("Feed hash is not set for the product id: ". $row['productId']);
                    unset($data[$key]);
                    continue ;
                }
                $identifier = $this->hashBuilder->buildIdentifierFromFeedTableRow($row, $metadata);
                $row = $this->serializer->unserialize($row['feed_data']);
                $row['deleted'] = true;
            } else {
                if (!\array_key_exists('deleted', $row)) {
                    $row['deleted'] = false;
                }
                $identifier = $this->hashBuilder->buildIdentifierFromFeedItem($row, $metadata);
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
            $this->addModifiedAtField($row, $metadata);
            $data[$identifier] = [
                'hash' => $hash,
                'feed' => $row,
                'deleted' => $deleted
            ];
        }
        return $data;
    }

    /**
     * Get feed table primary key
     *
     * @param FeedIndexMetadata $metadata
     * @return array
     */
    private function getFeedTablePrimaryKey(FeedIndexMetadata $metadata): array
    {
        if (!isset($this->feedTablePrimaryKey[$metadata->getFeedName()])) {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName($metadata->getFeedTableName());
            $indexList = $connection->getIndexList($table);
            $this->feedTablePrimaryKey[$metadata->getFeedName()] = $indexList[
                $connection->getPrimaryKeyName($table)
            ]['COLUMNS_LIST'];
        }
        return $this->feedTablePrimaryKey[$metadata->getFeedName()];
    }

    /**
     * Add modified at field to each row
     *
     * @param array $dataRow
     * @param FeedIndexMetadata $metadata
     * @return void
     */
    private function addModifiedAtField(&$dataRow, FeedIndexMetadata $metadata): void
    {
        $dataRow['modifiedAt'] = $metadata->getCurrentModifiedAtTimeInDBFormat();
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
     * @throws \Zend_Db_Statement_Exception
     */
    private function handleDeletedItems(
        array                   $ids,
        IndexStateProvider      $indexStateProvider,
        FeedIndexMetadata       $metadata,
        DataSerializerInterface $serializer
    ): void {
        foreach ($this->deletedEntitiesProvider->get(
            $ids,
            $indexStateProvider->getProcessedHashes(),
            $metadata
        ) as $feedItems) {
            $feedItems = $this->addHashes($feedItems, $metadata, true);
            $data = $this->filterFeedItems($feedItems, $metadata);

            if (empty($data)) {
                continue;
            }
            $indexStateProvider->addItems($data);
            if ($indexStateProvider->isBatchLimitReached()) {
                $this->exportFeedItemsAndLogStatus($indexStateProvider, $metadata, $serializer);
            }
        }
    }
}
