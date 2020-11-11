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
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MarkRemovedEntitiesQuery
     */
    private $markRemovedEntitiesQuery;

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
    public function execute(array $ids, FeedIndexMetadata $metadata): void
    {
        $select = $this->markRemovedEntitiesQuery->getQuery($ids, $metadata);
        $connection = $this->resourceConnection->getConnection();

        $update = $connection->updateFromSelect(
            $select,
            ['f' => $this->resourceConnection->getTableName($metadata->getFeedTableName())]
        );

        $connection->query($update);
    }
}
