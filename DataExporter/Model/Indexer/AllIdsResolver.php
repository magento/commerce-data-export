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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Returns generator with all IDs of the entity defined in Feed
 */
class AllIdsResolver
{
    /**
     * Used as a field name in Select statement
     */
    private const IDENTITY_FIELD_NAME = '_identity_field';

    /**
     * Limit for select deleted items query
     */
    private const SELECT_FOR_DELETE_LIMIT = 10000;

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
     * Returns generator with IDs
     *
     * @param FeedIndexMetadata $metadata
     * @param int $lastKnownId
     * @return \Generator|null
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAllDeletedIds(FeedIndexMetadata $metadata, int $lastKnownId = -1): ?\Generator
    {
        if ($metadata->isExportImmediately() && $metadata->isRemovable()) {
            $connection = $this->resourceConnection->getConnection();
            $cursor = $connection->query($this->getDeleteIdsSelect($lastKnownId, $metadata));
            $n = 0;
            $ids = [];
            while ($row = $cursor->fetch()) {
                $n++;
                $ids[] = $row;
                if ($n % $metadata->getBatchSize() === 0) {
                    yield $ids;
                    $ids = [];
                }
                if ($n === self::SELECT_FOR_DELETE_LIMIT) {
                    yield from $this->getAllDeletedIds($metadata, (int)$row[self::IDENTITY_FIELD_NAME]);
                }
            }
            if ($ids) {
                yield $ids;
            }
        }
    }

    /**
     * Get Ids select
     *
     * @param int $lastKnownId
     * @param FeedIndexMetadata $metadata
     * @return Select
     */
    private function getDeleteIdsSelect(int $lastKnownId, FeedIndexMetadata $metadata): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $tableSource = $this->resourceConnection->getTableName($metadata->getSourceTableName());
        $tableFeed = $this->resourceConnection->getTableName($metadata->getFeedTableName());
        $identityField = $metadata->getFeedTableField();

        $columnExpression = sprintf('f.%s', $identityField);
        $whereClause = sprintf('f.%s > ?', $identityField);

        $select = $connection->select()
            ->from(
                ['f' => $tableFeed],
                [
                    $metadata->getFeedIdentity() => 'f.' . $metadata->getFeedTableField(),
                    self::IDENTITY_FIELD_NAME => 'f.' . $identityField
                ]
            )
            ->joinLeft(
                ['s' => $tableSource],
                "f.{$metadata->getFeedTableField()} = s.{$metadata->getSourceTableField()}",
                []
            )
            ->where("f.is_deleted != 1")
            ->where("s.{$metadata->getSourceTableField()} is null")
            ->where($whereClause, $lastKnownId);

        return $select
            ->order($columnExpression)
            ->limit(self::SELECT_FOR_DELETE_LIMIT);
    }

    /**
     * Returns generator with IDs
     *
     * @param FeedIndexMetadata $metadata
     * @return \Generator|null
     */
    public function getAllIds(FeedIndexMetadata $metadata): ?\Generator
    {
        $connection = $this->resourceConnection->getConnection();
        $lastKnownId = -1;
        $continueReindex = true;
        $dateTime = date_create();

        while ($continueReindex) {
            $ids = $connection->fetchAll($this->getIdsSelect((int)$lastKnownId, $metadata, $dateTime));
            if (empty($ids)) {
                $continueReindex = false;
            } else {
                yield $ids;
                $lastKnownId = end($ids)[self::IDENTITY_FIELD_NAME];
            }
        }
    }

    /**
     * Get Ids select
     *
     * @param int $lastKnownId
     * @param FeedIndexMetadata $metadata
     * @param DateTime $dateTime
     * @return Select
     */
    private function getIdsSelect(int $lastKnownId, FeedIndexMetadata $metadata, DateTime $dateTime): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName($metadata->getSourceTableName());

        $columnExpression = sprintf('s.%s', $metadata->getSourceTableIdentityField());
        $whereClause = sprintf('s.%s > ?', $metadata->getSourceTableIdentityField());

        $select = $connection->select()
            ->from(
                ['s' => $tableName],
                [
                    $metadata->getFeedIdentity() => 's.' . $metadata->getSourceTableField(),
                    self::IDENTITY_FIELD_NAME => 's.' . $metadata->getSourceTableIdentityField()
                ]
            )
            ->where($whereClause, $lastKnownId);

        if ($metadata->getFullReIndexSecondsLimit() != 0) {
            $select->where(sprintf(
                "s.%s >= DATE_SUB(STR_TO_DATE('%s', '%%Y-%%m-%%d %%H:%%i:%%s'), INTERVAL %d SECOND)",
                $metadata->getSourceTableFieldOnFullReIndexLimit(),
                $dateTime->format('Y-m-d H:i:s'),
                $metadata->getFullReIndexSecondsLimit()
            ));
        }

        return $select
            ->order($columnExpression)
            ->limit($metadata->getBatchSize());
    }
}
