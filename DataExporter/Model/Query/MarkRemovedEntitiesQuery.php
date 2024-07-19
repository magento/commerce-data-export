<?php
/**
 * Copyright 2023 Adobe
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

namespace Magento\DataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Mark removed entities select query provider
 */
class MarkRemovedEntitiesQuery
{
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get select query for marking removed entities
     *
     * @param array $ids
     * @param FeedIndexMetadata $metadata
     *
     * @return Select
     */
    public function getQuery(array $ids, FeedIndexMetadata $metadata): Select
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(
                ['f' => $this->resourceConnection->getTableName($metadata->getFeedTableName())]
            )
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                \sprintf('f.%s = s.%s', $metadata->getFeedTableField(), $metadata->getSourceTableField()),
                []
            )
            ->where(\sprintf('f.%s IN (?)', $metadata->getFeedTableField()), $ids)
            ->where(\sprintf('s.%s IS NULL', $metadata->getSourceTableField()));
    }
}
