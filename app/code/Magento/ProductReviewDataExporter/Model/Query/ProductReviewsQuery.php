<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Review\Model\Review;
use Magento\Store\Model\Store;

/**
 * Product reviews query for provider
 */
class ProductReviewsQuery
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
     * @param int[] $reviewIds
     *
     * @return Select
     */
    public function getQuery(array $reviewIds) : Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['r' => $this->resourceConnection->getTableName('review')], [])
            ->join(
                ['rd' => $this->resourceConnection->getTableName('review_detail')],
                'r.review_id = rd.review_id',
                []
            )
            ->join(
                ['rs' => $this->resourceConnection->getTableName('review_store')],
                'r.review_id = rs.review_id',
                []
            )
            ->join(
                ['s' => $this->resourceConnection->getTableName('store')],
                'rs.store_id = s.store_id',
                []
            )
            ->join(
                ['re' => $this->resourceConnection->getTableName('review_entity')],
                'r.entity_id = re.entity_id',
                []
            )
            ->joinLeft(
                ['rov' => $this->resourceConnection->getTableName('rating_option_vote')],
                'r.review_id = rov.review_id',
                []
            )
            ->joinLeft(
                ['ro' => $this->resourceConnection->getTableName('rating_option')],
                'rov.option_id = ro.option_id',
                []
            )->columns(
                [
                    'reviewId' => 'r.review_id',
                    'productId' => 'r.entity_pk_value',
                    'visibility' => new Expression('GROUP_CONCAT(s.code)'),
                    'title' => 'rd.title',
                    'nickname' => 'rd.nickname',
                    'text' => 'rd.detail',
                    'customerId' => 'rd.customer_id',
                    'ratingId' => 'rov.rating_id',
                    'ratingValue' => 'ro.value',
                ]
            )
            ->where('r.review_id IN (?)', $reviewIds)
            ->where('r.status_id = ?', Review::STATUS_APPROVED)
            ->where('s.store_id != ?', Store::DEFAULT_STORE_ID)
            ->where('re.entity_code = ?', Review::ENTITY_PRODUCT_CODE)
            ->group(['r.review_id', 'rov.rating_id']);
    }
}
