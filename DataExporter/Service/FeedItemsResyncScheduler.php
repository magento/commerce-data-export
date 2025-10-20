<?php
/**
 * Copyright 2025 Adobe
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

namespace Magento\DataExporter\Service;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Indexer\IndexStateProvider;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Model\Query\FeedQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Mview\ViewFactory;

/**
 * Reset feed items to be reprocessed
 */
class FeedItemsResyncScheduler
{
    private const INSERT_TO_CHANGELOG_BATCH_SIZE = 500;

    private const ITEM_HASH_RESET_VALUE = '-';

    private array $feedTableColumns = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param ViewFactory $viewFactory
     * @param FeedQuery $feedQuery
     * @param CommerceDataExportLoggerInterface $logger
     * @param FeedIndexerProvider $feedIndexerProvider
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly ViewFactory $viewFactory,
        private readonly FeedQuery $feedQuery,
        private readonly CommerceDataExportLoggerInterface $logger,
        private readonly FeedIndexerProvider $feedIndexerProvider,
    ) {
    }

    /**
     * Schedule resync:
     *  - if $idsToResync is null - mark indexer as invalid
     *  - if $idsToResync > - add items to changelog
     *
     * Returns:
     *  - -1 when $idsToResync is null indexer is marked as invalid
     *  - 0 if not supported operation or empty list provided
     *  - >0 number of scheduled items
     *
     * @param FeedIndexMetadata $metadata
     * @param array|null $idsToResync
     *
     * @return int
     */
    public function execute(
        FeedIndexMetadata $metadata,
        ?array $idsToResync
    ): int {
        $submitted = 0;
        $dataToUpdate = [];

        // Skip operation for non-immediate export feeds
        if (!$metadata->isExportImmediately() || ($idsToResync !== null && empty($idsToResync))) {
            return $submitted;
        }
        try {
            if (null === $idsToResync) {
                $indexer = $this->feedIndexerProvider->getIndexer($metadata);
                $this->logger->initSyncLog($metadata, 'invalidate');
                $indexer->invalidate();
                $this->logger->complete();

                return -1;
            }
            $this->logger->initSyncLog($metadata, 'schedule_resync');
            while (!empty($idsToResync)) {
                $itemsBatch = array_splice($idsToResync, 0, self::INSERT_TO_CHANGELOG_BATCH_SIZE);
                $this->scheduleFeedItemsUpdate($metadata, $submitted, $itemsBatch);
            }

            $this->logger->logProgress($submitted, $submitted);
            $this->logger->complete();
        } catch (\Throwable $e) {
            $this->logError($e, $metadata, $dataToUpdate);
            return $submitted;
        }

        return $submitted;
    }

    /**
     * Log error
     *
     * @param \Throwable $exception
     * @param FeedIndexMetadata $metadata
     * @param array $dataForInsert
     * @return void
     */
    private function logError(\Throwable $exception, FeedIndexMetadata $metadata, array $dataForInsert): void
    {
        $feedItems = array_merge(
            $dataForInsert[IndexStateProvider::INSERT_OPERATION] ?? [],
            $dataForInsert[IndexStateProvider::UPDATE_OPERATION] ?? []
        );
        $this->logger->error(
            'Cannot schedule resync for feeds',
            [
                'feed' => $metadata->getFeedName(),
                'source_ids' => \array_unique(
                    array_column($feedItems, FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID)
                ),
                'feed_ids' => array_column($feedItems, FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID),
                'error' => $exception->getMessage(),
                'error_class' => get_class($exception)
            ]
        );
    }

    /**
     * Get feed table columns
     *
     * @param FeedIndexMetadata $metadata
     * @return array
     */
    private function getFeedTableColumns(FeedIndexMetadata $metadata): array
    {
        if (!isset($this->feedTableColumns[$metadata->getFeedName()])) {
            $columns = array_keys(
                $this->resourceConnection->getConnection()->describeTable(
                    $this->resourceConnection->getTableName($metadata->getFeedTableName())
                )
            );
            $this->feedTableColumns[$metadata->getFeedName()] = array_combine($columns, $columns);
        }
        return $this->feedTableColumns[$metadata->getFeedName()];
    }

    /**
     * Save feed data into database
     *
     * @param array $dataForInsert
     * @param FeedIndexMetadata $metadata
     * @param int $submitted
     * @return void
     */
    private function resetFeedItems(array $dataForInsert, FeedIndexMetadata $metadata, int &$submitted): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName($metadata->getFeedTableName());

        $fieldsToUpdateOnDuplicate = array_intersect_key(
            [
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH => FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH
            ],
            $this->getFeedTableColumns($metadata)
        );
        $connection->insertOnDuplicate(
            $tableName,
            $dataForInsert,
            $fieldsToUpdateOnDuplicate
        );

        //also Update CL table
        $updatedIds = array_unique(array_column($dataForInsert, FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID));
        $this->addIdsToChangelog($metadata, $updatedIds);

        $submitted += \count($dataForInsert);
    }

    /**
     * Schedule feed items update
     *
     * @param FeedIndexMetadata $metadata
     * @param int $submitted
     * @param array $entityIds
     * @return void
     */
    private function scheduleFeedItemsUpdate(
        FeedIndexMetadata $metadata,
        int &$submitted,
        array $entityIds
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $modifiedAt = (new \DateTime())->format('Y-m-d H:i:s');
        $dataToUpdate = [];
        $batchSize = self::INSERT_TO_CHANGELOG_BATCH_SIZE;

        $entityIds = array_unique($entityIds);
        $entityIds = array_combine($entityIds, $entityIds);

        $itemsToResetStatusQuery = $this->feedQuery->getFeedIdsSelect($metadata, $entityIds);
        foreach ($connection->fetchAll($itemsToResetStatusQuery) as $row) {
            $dataToUpdate[] = [
                FeedIndexMetadata::FEED_TABLE_FIELD_PK
                => (int)$row[FeedIndexMetadata::FEED_TABLE_FIELD_PK],
                FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID
                => (int)$row[FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID],
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH => self::ITEM_HASH_RESET_VALUE,
                FeedIndexMetadata::FEED_TABLE_FIELD_MODIFIED_AT => $modifiedAt,
            ];
            unset($entityIds[$row[FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID]]);

            // TODO: update from select?
            if (\count($dataToUpdate) === $batchSize) {
                $this->resetFeedItems($dataToUpdate, $metadata, $submitted);
                $dataToUpdate = [];
            }
        }

        // cover case when new entity is added to source table but not yet in feed table
        if (!empty($entityIds)) {
            $this->addIdsToChangelog($metadata, $entityIds);
            $submitted += \count($entityIds);
        }

        if (!empty($dataToUpdate)) {
            $this->resetFeedItems($dataToUpdate, $metadata, $submitted);
        }
    }

    /**
     * Add ids to changelog table
     *
     * @param FeedIndexMetadata $metadata
     * @param array $ids
     * @return void
     */
    private function addIdsToChangelog(FeedIndexMetadata $metadata, array $ids): void
    {
        $connection = $this->resourceConnection->getConnection();
        $viewId = $metadata->getFeedTableName();
        $view = $this->viewFactory->create()->load($viewId);
        $sourceTableName = $this->resourceConnection->getTableName($view->getChangelog()->getName());
        $sourceTableField = $view->getChangelog()->getColumnName();

        $connection->insertArray($sourceTableName, [$sourceTableField], $ids);
    }
}
