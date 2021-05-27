<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

/**
 * Class responsible for providing feed data
 */
class Feed implements FeedInterface
{
    /**
     * Offset
     *
     * @var int
     */
    protected const OFFSET = 100;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var FeedIndexMetadata
     */
    protected $feedIndexMetadata;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SerializerInterface $serializer
     * @param FeedIndexMetadata $feedIndexMetadata
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SerializerInterface $serializer,
        FeedIndexMetadata $feedIndexMetadata
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->serializer = $serializer;
        $this->feedIndexMetadata = $feedIndexMetadata;
    }

    /**
     * @inheritDoc
     */
    public function getFeedSince(string $timestamp, ?array $storeViewCodes = [], array $attributes = []): array
    {
        $modifiedAt = $timestamp === '1' ? (int)$timestamp : $timestamp;
        $connection = $this->resourceConnection->getConnection();

        $limit = $connection->fetchOne(
            $connection->select()
                ->from(
                    ['t' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                    [ 'modified_at']
                )
                ->where('t.modified_at > ?', $modifiedAt)
                ->order('modified_at')
                ->limit(1, self::OFFSET)
        );
        $select = $connection->select()
            ->from(
                ['t' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                [
                    'feed_data',
                    'modified_at',
                    'is_deleted'
                ]
            )
            ->where('t.modified_at > ?', $modifiedAt);
        if ($limit) {
            $select->where('t.modified_at <= ?', $limit);
        }

        if (!empty($storeViewCodes)) {
            $select->where('t.store_view_code IN (?)', $storeViewCodes);
        }

        return $this->fetchData($select, $attributes);
    }

    /**
     * @inheritDoc
     */
    public function getFeedByIds(array $ids, ?array $storeViewCodes = [], array $attributes = []): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['t' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                [
                    'feed_data',
                    'modified_at',
                    'is_deleted'
                ]
            )
            ->where('t.is_deleted = ?', 0)
            ->where(sprintf('t.%s IN (?)', $this->feedIndexMetadata->getFeedTableField()), $ids);

        if (!empty($storeViewCodes)) {
            $select->where('t.store_view_code IN (?)', $storeViewCodes);
        }

        return $this->fetchData($select, $attributes);
    }

    /**
     * @inheritDoc
     * @throws \Zend_Db_Statement_Exception
     */
    public function getDeletedByIds(array $ids, ?array $storeViewCodes = []): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['t' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                [
                    'feed_data',
                ]
            )
            ->where('t.is_deleted = ?', 1)
            ->where(sprintf('t.%s IN (?)', $this->feedIndexMetadata->getFeedTableField()), $ids);

        if (!empty($storeViewCodes)) {
            $select->where('t.store_view_code IN (?)', $storeViewCodes);
        }

        $connection = $this->resourceConnection->getConnection();
        $cursor = $connection->query($select);

        $output = [];
        while ($row = $cursor->fetch()) {
            $output[] = $this->serializer->unserialize($row['feed_data']);
        }

        return $output;
    }

    /**
     * Fetch data from prepared select statement
     *
     * @param string|\Magento\Framework\DB\Select $select
     * @param array $attributes
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    protected function fetchData($select, array $attributes): array
    {
        $connection = $this->resourceConnection->getConnection();
        $recentTimestamp = null;

        $cursor = $connection->query($select);
        $feedIdentity = $this->feedIndexMetadata->getFeedIdentity();
        $output = [];

        while ($row = $cursor->fetch()) {
            $dataRow = $this->serializer->unserialize($row['feed_data']);

            if (!empty($attributes[$dataRow[$feedIdentity]])) {
                $dataRow = $this->filterDataRow($dataRow, $attributes[$dataRow[$feedIdentity]]);
            }

            $dataRow['modifiedAt'] = $row['modified_at'];
            $dataRow['deleted'] = (bool) $row['is_deleted'];
            $output[] = $dataRow;
            if ($recentTimestamp === null || $recentTimestamp < $row['modified_at']) {
                $recentTimestamp = $row['modified_at'];
            }
        }
        return [
            'recentTimestamp' => $recentTimestamp,
            'feed' => $output,
        ];
    }

    /**
     * Filter data row
     *
     * @param array $dataRow
     * @param array $attributes
     *
     * @return array
     */
    private function filterDataRow(array $dataRow, array $attributes)
    {
        return \array_filter(
            $dataRow,
            function ($code) use ($attributes) {
                return \in_array($code, $attributes) || $code === $this->feedIndexMetadata->getFeedIdentity();
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
