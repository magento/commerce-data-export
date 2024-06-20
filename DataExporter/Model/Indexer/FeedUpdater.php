<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\DeadlockException;

class FeedUpdater
{
    private const RETRY_ATTEMPTS = 2;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var array
     */
    private array $feedTableColumns = [];

    /**
     * @var CommerceDataExportLoggerInterface
     */
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Execute data
     *
     * @param array $feedData
     * @param ?FeedExportStatus $exportStatus
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     */
    public function execute(
        array $feedData,
        ?FeedExportStatus $exportStatus,
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer
    ): void {
        for ($i = 1; $i <= self::RETRY_ATTEMPTS; $i++) {
            $lastAttempt = $i === self::RETRY_ATTEMPTS;
            if ($this->insertUpdateWithRetry($feedData, $exportStatus, $metadata, $serializer, $lastAttempt)) {
                break;
            }
        }
    }

    /**
     * Insert or update feed data with retry if deadlock exception occurred
     *
     * @param array $feedData
     * @param FeedExportStatus|null $exportStatus
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param bool $lastAttempt
     * @return bool
     */
    private function insertUpdateWithRetry(
        array $feedData,
        ?FeedExportStatus $exportStatus,
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        bool $lastAttempt
    ): bool {
        try {
            $dataForInsert = $serializer->serialize($feedData, $exportStatus, $metadata);
            if (!empty($dataForInsert)) {
                // Skip data insert if feed submit was skipped
                if (null !== $exportStatus
                    && $exportStatus->getStatus()->getValue() === ExportStatusCodeProvider::FEED_SUBMIT_SKIPPED) {
                    return true;
                }

                $submitted = 0;
                $this->saveFeedData($dataForInsert, $metadata, $submitted);

                $this->logger->logProgress(null, $submitted);
            }
        } catch (DeadlockException $deadlockException) {
            if ($lastAttempt) {
                $this->logError($deadlockException, $metadata, $dataForInsert);
            }
            return false;
        } catch (\Throwable $e) {
            $this->logError($e, $metadata, $dataForInsert);
        }
        return true;
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
            'Cannot persist export status to feed table',
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
    private function saveFeedData(array $dataForInsert, FeedIndexMetadata $metadata, int &$submitted): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName($metadata->getFeedTableName());

        // Insert data for legacy feeds. Also supports third-party implementations based on legacy approach
        if (!$metadata->isExportImmediately()) {
            $fieldsToUpdateOnDuplicate = array_intersect_key(
                $metadata->getFeedTableMutableColumns(),
                $this->getFeedTableColumns($metadata)
            );

            $connection->insertOnDuplicate(
                $tableName,
                $dataForInsert,
                $fieldsToUpdateOnDuplicate
            );

            $submitted += count($dataForInsert);
        } else {
            if (!empty($dataForInsert[IndexStateProvider::UPDATE_OPERATION])) {
                $fieldsToUpdateOnDuplicate = array_intersect_key(
                    $metadata->getFeedTableMutableColumns(),
                    $this->getFeedTableColumns($metadata)
                );
                $connection->insertOnDuplicate(
                    $tableName,
                    $dataForInsert[IndexStateProvider::UPDATE_OPERATION],
                    $fieldsToUpdateOnDuplicate
                );
                $submitted += count($dataForInsert[IndexStateProvider::UPDATE_OPERATION]);
            }
            if (!empty($dataForInsert[IndexStateProvider::INSERT_OPERATION])) {
                $columns = array_keys($dataForInsert[IndexStateProvider::INSERT_OPERATION][0]);
                $connection->insertArray(
                    $tableName,
                    $columns,
                    $dataForInsert[IndexStateProvider::INSERT_OPERATION],
                );
                $submitted += count($dataForInsert[IndexStateProvider::INSERT_OPERATION]);
            }
        }
    }
}
