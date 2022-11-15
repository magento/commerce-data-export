<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
