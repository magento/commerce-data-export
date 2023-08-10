<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ResourceConnection;
use \Magento\DataExporter\Export\Processor as ExportProcessor;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\DataExporter\Model\FeedHashBuilder;

/**
 * Base implementation of feed indexing behaviour, does not care about deleted entities
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FeedIndexProcessorCreateUpdate implements FeedIndexProcessorInterface
{
    private const MODIFIED_AT_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    private ExportProcessor $exportProcessor;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @var ExportFeedInterface
     */
    private $exportFeedProcessor;
    private FeedUpdater $feedUpdater;
    private FeedHashBuilder $hashBuilder;
    private SerializerInterface $serializer;

    private $feedTablePrimaryKey;

    private string $modifiedAtTimeInDBFormat;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ExportProcessor $exportProcessor
     * @param ExportFeedInterface $exportFeedProcessor
     * @param FeedUpdater $feedUpdater
     * @param FeedHashBuilder $hashBuilder
     * @param SerializerInterface $serializer
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ExportProcessor $exportProcessor,
        ExportFeedInterface $exportFeedProcessor,
        FeedUpdater $feedUpdater,
        FeedHashBuilder $hashBuilder,
        SerializerInterface $serializer,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->exportProcessor = $exportProcessor;
        $this->exportFeedProcessor = $exportFeedProcessor;
        $this->feedUpdater = $feedUpdater;
        $this->hashBuilder = $hashBuilder;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @inerhitDoc
     *
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param EntityIdsProviderInterface $idsProvider
     * @param array $ids
     * @param callable|null $callback
     */
    public function partialReindex(
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        EntityIdsProviderInterface $idsProvider,
        array $ids = [],
        callable $callback = null
    ): void {
        $feedIdentity = $metadata->getFeedIdentity();
        $arguments = [];
        foreach ($idsProvider->getAffectedIds($metadata, $ids) as $id) {
            $arguments[] = [$feedIdentity => $id];
        }
        $this->modifiedAtTimeInDBFormat = (new \DateTime())->format(self::MODIFIED_AT_FORMAT);
        $data = $this->exportProcessor->process($metadata->getFeedName(), $arguments);

        $exportStatus = null;
        if ($metadata->isExportImmediately()) {
            $feedItemsToDelete = $callback !== null ? $callback() : [];
            $data = $this->prepareFeedBeforeSubmit($data, $feedItemsToDelete, $metadata);
            if (empty($data)) {
                return;
            }
            $exportStatus = $this->exportFeedProcessor->export(
                array_column($data, 'feed'),
                $metadata
            );
        }

        $this->feedUpdater->execute($data, $exportStatus, $metadata, $serializer);

        if ($callback !== null && !$metadata->isExportImmediately()) {
            $callback();
        }
    }

    /**
     * {@inerhitDoc}
     *
     * @param FeedIndexMetadata $metadata
     * @param DataSerializerInterface $serializer
     * @param EntityIdsProviderInterface $idsProvider
     * @throws \Zend_Db_Statement_Exception
     */
    public function fullReindex(
        FeedIndexMetadata $metadata,
        DataSerializerInterface $serializer,
        EntityIdsProviderInterface $idsProvider
    ): void {
        try {
            $this->truncateIndexTable($metadata);
            foreach ($idsProvider->getAllIds($metadata) as $batch) {
                $ids = \array_column($batch, $metadata->getFeedIdentity());
                $this->partialReindex($metadata, $serializer, $idsProvider, $ids);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
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
     * Prepare feed data before submit:
     * - calculate feed identifier and feed hash
     * - remove unchanged feed items (items with the same hash)
     *
     * @param array $feedItems
     * @param array|null $feedItemsToDelete
     * @param FeedIndexMetadata $metadata
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function prepareFeedBeforeSubmit(
        array $feedItems,
        ?array $feedItemsToDelete,
        FeedIndexMetadata $metadata
    ) : array {
        $feedItemsToDelete = !empty($feedItemsToDelete) ? $this->addHashes($feedItemsToDelete, $metadata, true) : [];
        $feedItems = $this->addHashes($feedItems, $metadata);
        $feedItems = array_merge($feedItems, $feedItemsToDelete);
        if (empty($feedItems)) {
            return [];
        }
        return $this->filterFeedItems($feedItems, $metadata);
    }

    /**
     * Remove feed items from further processing if all true:
     * - item hash didn't change
     * - previous export status is non-retryable
     *
     * @param array $feedItems
     * @param FeedIndexMetadata $metadata
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function filterFeedItems(array $feedItems, FeedIndexMetadata $metadata) : array
    {
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
                    continue ;
                }
                $identifier = $this->hashBuilder->buildIdentifierFromFeedTableRow($row, $metadata);
                $row = $this->serializer->unserialize($row['feed_data']);
                $row['deleted'] = true;
            } else {
                $identifier = $this->hashBuilder->buildIdentifierFromFeedItem($row, $metadata);
            }
            unset($data[$key]);

            $hash = $this->hashBuilder->buildHash($row, $metadata);
            $this->addModifiedAtField($row);
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
     * @return void
     */
    private function addModifiedAtField(&$dataRow): void
    {
        $dataRow['modifiedAt'] = $this->modifiedAtTimeInDBFormat;
    }
}
