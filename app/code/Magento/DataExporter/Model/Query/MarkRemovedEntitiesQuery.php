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

/**
 * Mark removed entities select query provider
 */
class MarkRemovedEntitiesQuery
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
                \sprintf('f.%s = s.%s', $metadata->getFeedTableField(), $metadata->getSourceTableField()),
                ['is_deleted' => new \Zend_Db_Expr('1')]
            )
            ->where(\sprintf('f.%s IN (?)', $metadata->getFeedTableField()), $ids);

        if ($metadata->getScopeTableName()) {
            $select
                ->join(
                    ['st' => $this->resourceConnection->getTableName('store')],
                    'f.store_view_code = st.code',
                    []
                )
                ->joinLeft(
                    ['sc' => $this->resourceConnection->getTableName($metadata->getScopeTableName())],
                    \sprintf(
                        'sc.%s = s.%s AND sc.%3$s = st.%3$s',
                        $metadata->getScopeTableField(),
                        $metadata->getSourceTableField(),
                        $metadata->getScopeCode(),
                    ),
                    []
                )
                ->where(\sprintf('sc.%s IS NULL', $metadata->getScopeTableField()));
        } else {
            $select->where(\sprintf('s.%s IS NULL', $metadata->getSourceTableField()));
        }

        return $select;
    }
}
