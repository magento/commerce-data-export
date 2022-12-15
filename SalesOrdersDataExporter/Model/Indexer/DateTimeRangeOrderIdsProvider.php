<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Indexer;

use DateTime;
use Exception;
use Generator;
use Magento\DataExporter\Model\Indexer\EntityIdsProviderInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\BatchIteratorFactory;

/**
 * Returns IDs needed by indexer for a given feed.
 */
class DateTimeRangeOrderIdsProvider implements EntityIdsProviderInterface
{
    private ResourceConnection $resourceConnection;
    private BatchIteratorFactory $batchIteratorFactory;
    private DateTime $from;
    private DateTime $to;

    public function __construct(ResourceConnection $resourceConnection, BatchIteratorFactory $batchIteratorFactory, DateTime $to, DateTime $from)
    {
        $this->from = $from;
        $this->to = $to;
        $this->resourceConnection = $resourceConnection;
        $this->batchIteratorFactory = $batchIteratorFactory;
    }

    /**
     * @inheritdoc
     *
     * @param FeedIndexMetadata $metadata
     * @return Generator|null
     */
    public function getAllIds(FeedIndexMetadata $metadata): ?Generator
    {
        $tableName = $this->resourceConnection->getTableName($metadata->getSourceTableName());
        return $this->findOrders($this->from, $this->to, $tableName);
    }

    /**
     * @param FeedIndexMetadata $metadata
     * @param array $ids
     * @return array
     * @throws Exception
     */
    public function getAffectedIds(FeedIndexMetadata $metadata, array $ids): array
    {
        throw new Exception('Not implemented, only `getAllIds` is supported for this implementation');
    }

    private function findOrders(DateTime $from, DateTime $to, $tableName, int $batchSize = 50): Generator
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['order' => $tableName],
                ['entity_id']
            )
            ->where('order.created_at >= ?', $from)
            ->where('order.created_at <= ?', $to);

        $iterator = $this->batchIteratorFactory->create(
            [
                'select' => $select,
                'batchSize' => $batchSize,
                'rangeField' => 'entity_id',
            ]
        );

        foreach ($iterator as $batchSelect) {
            yield $connection->fetchCol($batchSelect);
        }
    }
}
