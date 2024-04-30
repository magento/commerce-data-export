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
            ->where('f.modified_at < ?', $recentTimeStamp);
    }
}
