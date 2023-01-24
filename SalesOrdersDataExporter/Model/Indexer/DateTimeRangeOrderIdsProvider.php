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
    private $resourceConnection;
    private $batchIteratorFactory;
    private $from;
    private $to;

    public function __construct(
        ResourceConnection $resourceConnection,
        BatchIteratorFactory $batchIteratorFactory,
        DateTime $from,
        DateTime $to
    ) {
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
        $fieldName = $metadata->getFeedIdentity();
        $tableName = $this->resourceConnection->getTableName($metadata->getSourceTableName());
        return $this->findOrders($this->from, $this->to, $fieldName, $tableName, $metadata->getBatchSize());
    }

    /**
     * @param FeedIndexMetadata $metadata
     * @param array $ids
     * @return array
     * @throws Exception
     */
    public function getAffectedIds(FeedIndexMetadata $metadata, array $ids): array
    {
        return $ids;
    }

    private function findOrders(
        DateTime $from,
        DateTime $to,
        string $fieldName,
        string $tableName,
        int $batchSize = 100
    ): Generator {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['order' => $tableName],
                [$fieldName => 'entity_id']
            )
            ->where('order.created_at >= ?', $from)
            ->where('order.created_at <= ?', $to);

        $iterator = $this->batchIteratorFactory->create(
            [
                'select' => $select,
                'batchSize' => $batchSize,
                'correlationName' => 'order',
                'rangeField' => 'entity_id',
                'rangeFieldAlias' => $fieldName,
            ]
        );

        foreach ($iterator as $batchSelect) {
            yield $connection->fetchAll($batchSelect);
        }
    }
}
