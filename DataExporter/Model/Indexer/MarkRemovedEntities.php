<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\Query\MarkRemovedEntitiesQuery;
use Magento\Framework\App\ResourceConnection;

/**
 * Action responsible for marking entities as removed
 */
class MarkRemovedEntities implements MarkRemovedEntitiesInterface
{
    private ResourceConnection $resourceConnection;
    private MarkRemovedEntitiesQuery $markRemovedEntitiesQuery;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MarkRemovedEntitiesQuery $markRemovedEntitiesQuery
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MarkRemovedEntitiesQuery $markRemovedEntitiesQuery
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->markRemovedEntitiesQuery = $markRemovedEntitiesQuery;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $ids, FeedIndexMetadata $metadata): ?array
    {
        $select = $this->markRemovedEntitiesQuery->getQuery($ids, $metadata);

        // convert select-object to sql-string with staging future
        $sqlSelect = $select->assemble();

        // make "update from select" statement
        $sqlUpdate = preg_replace('/SELECT .*? FROM/', 'UPDATE', $sqlSelect);
        $sqlUpdate = str_replace("WHERE", 'SET `f`.`is_deleted` = 1 WHERE', $sqlUpdate);

        $this->resourceConnection->getConnection()->query($sqlUpdate);

        return null;
    }
}
