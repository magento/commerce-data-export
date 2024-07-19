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

namespace Magento\ProductReviewDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Query\MarkRemovedEntitiesQuery as MainQuery;
use Magento\DataExporter\Model\Query\MarkRemovedEntitiesQuery as DefaultMarkRemovedEntitiesQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Review\Model\Review;
use Zend_Db;

/**
 * Mark removed entities select query provider
 */
class MarkRemovedEntitiesQuery extends DefaultMarkRemovedEntitiesQuery
{
    /**
     * Entities feed names
     */
    private const FEED_NAME_REVIEWS = 'reviews';
    private const FEED_NAME_RATING_METADATA = 'ratingMetadata';

    private MainQuery $mainQuery;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MainQuery $mainQuery
     */
    public function __construct(ResourceConnection $resourceConnection, MainQuery $mainQuery)
    {
        $this->mainQuery = $mainQuery;
        parent::__construct($resourceConnection);
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
        $select = $this->mainQuery->getQuery($ids, $metadata);

        if ($metadata->getFeedName() === self::FEED_NAME_REVIEWS) {
            $select->where('s.status_id != ?', Review::STATUS_APPROVED, Zend_Db::INT_TYPE);
        } elseif ($metadata->getFeedName() === self::FEED_NAME_RATING_METADATA) {
            $select->where('s.is_active != ?', 1, Zend_Db::INT_TYPE);
        }

        return $select;
    }
}
