<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\DataExporter\Model\Query\MarkRemovedEntitiesQuery as DefaultMarkRemovedEntitiesQuery;


/**
 * Mark removed entities select query provider
 */
class MarkRemovedEntitiesQuery extends DefaultMarkRemovedEntitiesQuery
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
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                \sprintf('f.product_id = s.%s', $metadata->getSourceTableField()),
                ['is_deleted' => new \Zend_Db_Expr('1')]
            )
            ->where('f.product_id IN (?)', $ids)
            ->where(\sprintf('s.%s IS NULL', $metadata->getSourceTableField()));

        return $select;
    }
}
