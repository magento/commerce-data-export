<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Provide list of deleted feed items according to the feed batch size
 */
interface DeletedEntitiesProviderInterface
{
    /**
     * Get entities to delete
     *
     * @param array $ids
     * @param array $filteredHashes see FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH
     * @param FeedIndexMetadata $metadata
     * @param string $recentTimeStamp
     * @return \Generator
     */
    public function get(
        array $ids,
        array $filteredHashes,
        FeedIndexMetadata $metadata,
        string $recentTimeStamp
    ): \Generator;
}
