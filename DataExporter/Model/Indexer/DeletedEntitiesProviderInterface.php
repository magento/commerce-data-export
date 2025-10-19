<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
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
