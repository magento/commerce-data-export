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

namespace Magento\DataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class DeletedEntitiesByModifiedAtQuery
{
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get query for deleted entities by modified at timestamp
     *
     * @param array $ids
     * @param FeedIndexMetadata $metadata
     * @param string $recentTimeStamp
     * @return Select
     */
    public function getQuery(array $ids, FeedIndexMetadata $metadata, string $recentTimeStamp): Select
    {
        return $this->resourceConnection->getConnection()->select()
            ->from(
                ['f' => $this->resourceConnection->getTableName($metadata->getFeedTableName())]
            )
            ->where(\sprintf('f.%s IN (?)', $metadata->getFeedTableField()), $ids)
            ->where('f.modified_at < ?', $recentTimeStamp)
            // return only un-deleted items to prevent extra processing
            ->where('f.is_deleted = ?', 0);
    }
}
