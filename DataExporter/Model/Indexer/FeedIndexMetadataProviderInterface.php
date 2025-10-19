<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Feed index metadata provider interface
 */
interface FeedIndexMetadataProviderInterface
{
    /**
     * Get feed index metadata for the feed
     *
     * @return FeedIndexMetadata
     */
    public function getFeedIndexMetadata(): FeedIndexMetadata;
}
