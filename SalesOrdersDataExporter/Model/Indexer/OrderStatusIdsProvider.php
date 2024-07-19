<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\SalesOrdersDataExporter\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Indexer\EntityIdsProviderInterface;

/**
 * Provide orders statuses entities to index
 */
class OrderStatusIdsProvider implements EntityIdsProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    public function getAllIds(FeedIndexMetadata $metadata) : ?\Generator
    {
        $connection = $this->resourceConnection->getConnection();
        yield $connection->fetchAll($this->getStatusesSelect($metadata));
    }

    /**
     * @inheritdoc
     */
    public function getAffectedIds(FeedIndexMetadata $metadata, array $ids): array
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->fetchAll($this->getStatusesSelect($metadata, $ids));
    }

    /**
     * Get statuses select
     *
     * @param FeedIndexMetadata $metadata
     * @param array $ids
     * @return Select
     */
    private function getStatusesSelect(FeedIndexMetadata $metadata, array $ids = []) : Select
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['s' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                [
                    $metadata->getFeedIdentity() =>
                        's.' . $metadata->getSourceTableField()
                ]
            )
            ->order('s.' .  $metadata->getSourceTableField());
        if (!empty($ids)) {
            $select->where($metadata->getSourceTableField() . ' IN(?)', $ids);
        }
        return $select;
    }
}
