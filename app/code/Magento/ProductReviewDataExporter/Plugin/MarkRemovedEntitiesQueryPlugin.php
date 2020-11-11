<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductReviewDataExporter\Plugin;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Query\MarkRemovedEntitiesQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Review\Model\Review;

/**
 * Plugin for marking inactive entities as removed (status change -> not approved review, inactive rating)
 */
class MarkRemovedEntitiesQueryPlugin
{
    /**
     * Entities feed names
     */
    private const FEED_NAME_REVIEWS = 'reviews';
    private const FEED_NAME_RATING_METADATA = 'ratingMetadata';

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
     * Add inactive entity condition for marking removed entities query
     *
     * @param MarkRemovedEntitiesQuery $subject
     * @param Select $result
     * @param array $ids
     * @param FeedIndexMetadata $metadata
     *
     * @return Select
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetQuery(
        MarkRemovedEntitiesQuery $subject,
        Select $result,
        array $ids,
        FeedIndexMetadata $metadata
    ): Select {
        $connection = $this->resourceConnection->getConnection();
        $whereCondition = [];

        if ($metadata->getFeedName() === self::FEED_NAME_REVIEWS) {
            $whereCondition[] = $connection->quoteInto('s.status_id != ?', Review::STATUS_APPROVED);
        } elseif ($metadata->getFeedName() === self::FEED_NAME_RATING_METADATA) {
            $whereCondition[] = $connection->quoteInto('s.is_active != ?', 1);
        }

        if (!empty($whereCondition)) {
            $whereCondition[] = $connection->quoteInto(\sprintf('f.%s IN (?)', $metadata->getFeedTableField()), $ids);
            $result->orWhere(\implode(' AND ', $whereCondition));
        }

        return $result;
    }
}
