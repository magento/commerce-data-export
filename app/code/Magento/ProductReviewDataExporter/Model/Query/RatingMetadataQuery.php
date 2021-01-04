<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;

/**
 * Rating metadata query for provider
 */
class RatingMetadataQuery
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
     * Get query for provider
     *
     * @param array $ratingIds
     *
     * @return Select
     */
    public function getQuery(array $ratingIds) : Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['r' => $this->resourceConnection->getTableName('rating')], [])
            ->join(
                ['rs' => $this->resourceConnection->getTableName('rating_store')],
                'r.rating_id = rs.rating_id',
                []
            )
            ->join(
                ['s' => $this->resourceConnection->getTableName('store')],
                'rs.store_id = s.store_id',
                []
            )
            ->joinLeft(
                ['rt' => $this->resourceConnection->getTableName('rating_title')],
                'r.rating_id = rt.rating_id AND rt.store_id = s.store_id',
                []
            )
            ->join(
                ['ro' => $this->resourceConnection->getTableName('rating_option')],
                'r.rating_id = ro.rating_id',
                []
            )
            ->columns(
                [
                    'ratingId' => 'r.rating_id',
                    'storeViewCode' => 's.code',
                    'name' => $connection->getIfNullSql('rt.value', 'r.rating_code'),
                    'valueId' => 'ro.option_id',
                    'value' => 'ro.value',
                    'position' => 'ro.position',
                ]
            )
            ->where('r.rating_id IN (?)', $ratingIds)
            ->where('r.is_active = ?', 1)
            ->where('s.store_id != ?', Store::DEFAULT_STORE_ID);
    }
}
