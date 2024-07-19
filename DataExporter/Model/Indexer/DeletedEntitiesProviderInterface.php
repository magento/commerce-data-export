<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
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
