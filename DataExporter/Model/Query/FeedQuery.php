<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Feed Queries source
 */
class FeedQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get limit select
     *
     * @param FeedIndexMetadata $metadata
     * @param string $modifiedAt
     * @param int $offset
     * @param array|null $ignoredExportStatus
     * @return Select
     */
    public function getLimitSelect(
        FeedIndexMetadata $metadata,
        string $modifiedAt,
        int $offset,
        array $ignoredExportStatus = null
    ): Select {
        $modifiedAt = $modifiedAt === '1' ? (int)$modifiedAt : $modifiedAt;
        $connection = $this->resourceConnection->getConnection();
        $feedTableName = $this->resourceConnection->getTableName($metadata->getFeedTableName());
        $select = $connection->select()
            ->from(
                ['t' => $feedTableName],
                ['modified_at']
            )
            ->where('t.modified_at > ?', $modifiedAt)
            ->order('modified_at')
            ->limit(1, $offset);

        $this->addFilterByStatus($select, $feedTableName, $ignoredExportStatus);

        return $select;
    }

    /**
     * Get select to retrieve data
     *
     * @param FeedIndexMetadata $metadata
     * @param string $modifiedAt
     * @param string|null $limit
     * @param array|null $ignoredExportStatus
     * @return Select
     */
    public function getDataSelect(
        FeedIndexMetadata $metadata,
        string $modifiedAt,
        ?string $limit,
        array $ignoredExportStatus = null
    ): Select {
        $modifiedAt = $modifiedAt === '1' ? (int)$modifiedAt : $modifiedAt;
        $connection = $this->resourceConnection->getConnection();
        $columns = [
            'feed_data',
            'modifiedAt' => 'modified_at'
        ];
        $feedTableName = $this->resourceConnection->getTableName($metadata->getFeedTableName());
        if ($connection->tableColumnExists($feedTableName, 'is_deleted')) {
            $columns['deleted'] = 'is_deleted';
        }
        $select = $connection->select()
            ->from(
                ['t' => $feedTableName],
                $columns
            )
            ->where('t.modified_at > ?', $modifiedAt);
        if ($limit) {
            $select->where('t.modified_at <= ?', $limit);
        }
        $this->addFilterByStatus($select, $feedTableName, $ignoredExportStatus);
        return $select;
    }

    /**
     * Add filter by status
     *
     * @param Select $select
     * @param string $feedTableName
     * @param ?array $ignoredExportStatus
     * @return void
     */
    private function addFilterByStatus(Select $select, string $feedTableName, array $ignoredExportStatus = null): void
    {
        if ($ignoredExportStatus === null) {
            return ;
        }
        $connection = $this->resourceConnection->getConnection();
        if (!$connection->tableColumnExists($feedTableName, FeedIndexMetadata::FEED_TABLE_FIELD_STATUS)) {
            throw new \RuntimeException(sprintf(
                'Feed table "%s" doesn\'t have "status" column.',
                $feedTableName
            ));
        }
        $select->where('t.status NOT IN (?)', $ignoredExportStatus);
    }
}
