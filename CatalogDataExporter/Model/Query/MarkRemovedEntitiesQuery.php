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
     * @param string $storeCode
     * @param FeedIndexMetadata $feedIndexMetadata
     * @throws \InvalidArgumentException
     *
     * @return Select
     */
    public function getQuery(array $ids, string $storeCode, FeedIndexMetadata $feedIndexMetadata): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $feedTableField = $feedIndexMetadata->getFeedTableField();
        if (empty($ids)) {
            throw new \InvalidArgumentException(
                'Ids list can not be empty'
            );
        }
        return $connection->select()
            ->joinInner(
                ['feed' => $this->resourceConnection->getTableName($feedIndexMetadata->getFeedTableName())],
                \sprintf(
                    'feed.%s = f.%s',
                    $feedTableField,
                    $feedTableField
                ),
                ['is_deleted' => new \Zend_Db_Expr('1')]
            )
            ->where(\sprintf('f.%s IN (?)', $feedTableField), $ids)
            ->where(\sprintf('f.%s = ?', 'store_view_code'), $storeCode);
    }
}
