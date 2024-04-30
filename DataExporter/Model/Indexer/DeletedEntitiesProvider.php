<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

use Magento\DataExporter\Model\Query\DeletedEntitiesByModifiedAtQuery;
use Magento\Framework\App\ResourceConnection;

class DeletedEntitiesProvider implements DeletedEntitiesProviderInterface
{
    private ResourceConnection $resourceConnection;
    private DeletedEntitiesByModifiedAtQuery $deletedEntitiesByModifiedAtQuery;

    /**
     * @param ResourceConnection $resourceConnection
     * @param DeletedEntitiesByModifiedAtQuery $deletedEntitiesByModifiedAtQuery
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DeletedEntitiesByModifiedAtQuery $deletedEntitiesByModifiedAtQuery
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->deletedEntitiesByModifiedAtQuery = $deletedEntitiesByModifiedAtQuery;
    }

    /**
     * @inheritDoc
     */
    public function get(
        array $ids,
        array $filteredHashes,
        FeedIndexMetadata $metadata,
        string $recentTimeStamp
    ): \Generator {
        $select = $this->deletedEntitiesByModifiedAtQuery->getQuery($ids, $metadata, $recentTimeStamp);
        $cursor = $this->resourceConnection->getConnection()->query($select);
        $deletedItems = [];
        $n = 0;
        while ($row = $cursor->fetch()) {
            if (isset($filteredHashes[$row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH]])) {
                continue;
            }
            $n++;
            $deletedItems[] = $row;
            if ($n % $metadata->getBatchSize() == 0) {
                yield $deletedItems;
                $deletedItems = [];
            }
        }
        if ($deletedItems) {
            yield $deletedItems;
        }
    }
}
