<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Mark product feed item as removed when:
 * - product deleted
 * - product unassigned from website
 */
class MarkRemovedEntitiesQuery extends \Magento\DataExporter\Model\Query\MarkRemovedEntitiesQuery
{
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($resourceConnection);
    }

    /**
     * @inheirtDoc
     *
     * @param array $ids
     * @param FeedIndexMetadata $metadata
     * @return Select
     */
    public function getQuery(array $ids, FeedIndexMetadata $metadata): Select
    {
        $select = parent::getQuery($ids, $metadata);
        $select->reset(\Magento\Framework\DB\Select::WHERE);
        $select->joinLeft(
            ['store' => $this->resourceConnection->getTableName('store')],
            'f.store_view_code = store.code',
            []
        )->joinLeft(
            ['w' => $this->resourceConnection->getTableName('store_website')],
            'store.website_id = w.website_id',
            []
        )->joinLeft(
            ['pw' => $this->resourceConnection->getTableName('catalog_product_website')],
            'f.id = pw.product_id AND pw.website_id = w.website_id',
            []
        )->where(\sprintf('f.%s IN (?)', $metadata->getFeedTableField()), $ids)
            ->where(\sprintf('s.%s IS NULL OR pw.website_id is NULL', $metadata->getSourceTableField()));

        return $select;
    }
}
