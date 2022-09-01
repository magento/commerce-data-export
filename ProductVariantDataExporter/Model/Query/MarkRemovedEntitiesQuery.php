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
        $fieldName = $metadata->getSourceTableField();

        return $connection->select()
            ->joinLeft(
                ['e' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                \sprintf('f.parent_id = e.%s', $fieldName),
                ['is_deleted' => new \Zend_Db_Expr('1')]
            )
            ->joinLeft(
                ['p' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                \sprintf('f.parent_id = p.%s', $fieldName),
                []
            )
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                \sprintf('s.product_id = e.%s AND s.parent_id = p.%s', $fieldName, $fieldName),
                []
            )
            ->where(\sprintf('f.product_id IN (?) OR e.%s IS NULL', $fieldName), $ids);
    }
}
