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
 * Provide entity to index
 */
class IndexEntityProvider implements IndexEntityProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param FeedIndexMetadata $feedIndexMetadata
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc 
     */
    public function getAllIds(FeedIndexMetadata $metadata) : ?\Generator
    {
        $connection = $this->resourceConnection->getConnection();
        $lastKnownId = 0;
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
     * @return Select
     */
    private function getIdsSelect(int $lastKnownId, FeedIndexMetadata $metadata) : Select
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
