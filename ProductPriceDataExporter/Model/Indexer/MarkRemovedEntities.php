<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Indexer;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Indexer\MarkRemovedEntitiesInterface;
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
    public function execute(array $ids, FeedIndexMetadata $metadata): void
    {
        $select = $this->markRemovedEntitiesQuery->getQuery($ids, $metadata);
        $connection = $this->resourceConnection->getConnection();

        $connection->query(
            $connection->deleteFromSelect($select, 'feed')
        );
    }
}
