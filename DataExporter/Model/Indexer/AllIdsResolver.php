<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Returns generator with all IDs of the entity defined in Feed
 */
class AllIdsResolver
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
        while ($continueReindex) {
            $ids = $connection->fetchAll($this->getIdsSelect((int)$lastKnownId, $metadata));
            if (empty($ids)) {
                $continueReindex = false;
            } else {
                yield $ids;
                $lastKnownId = end($ids)[$metadata->getFeedIdentity()];
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
    private function getIdsSelect(int $lastKnownId, FeedIndexMetadata $metadata): Select
    {
        $columnExpression = sprintf('s.%s', $metadata->getSourceTableField());
        $whereClause = sprintf('s.%s > ?', $metadata->getSourceTableField());
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(
                ['s' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                [
                    $metadata->getFeedIdentity() =>
                        's.' . $metadata->getSourceTableField()
                ]
            )
            ->where($whereClause, $lastKnownId)
            ->order($columnExpression)
            ->limit($metadata->getBatchSize());
    }
}
