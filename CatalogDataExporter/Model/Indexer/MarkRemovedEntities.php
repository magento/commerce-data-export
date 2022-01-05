<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Indexer;

use Magento\CatalogDataExporter\Model\Query\MarkRemovedEntitiesQuery;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;

/**
 * Action responsible for marking entities as removed
 */
class MarkRemovedEntities
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
     * Mark specific store catalog items as deleted
     *
     * @param array $ids
     * @param string $storeCode
     * @param FeedIndexMetadata $feedIndexMetadata
     * @throws \InvalidArgumentException
     */
    public function execute(array $ids, string $storeCode, FeedIndexMetadata $feedIndexMetadata): void
    {
        $select = $this->markRemovedEntitiesQuery->getQuery($ids, $storeCode, $feedIndexMetadata);
        $connection = $this->resourceConnection->getConnection();

        $update = $connection->updateFromSelect(
            $select,
            ['f' => $this->resourceConnection->getTableName($feedIndexMetadata->getFeedTableName())]
        );

        $connection->query($update);
    }
}
