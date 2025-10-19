<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Mark removed entities action interface
 */
interface MarkRemovedEntitiesInterface
{
    /**
     * Mark feed entities as removed
     *
     * @param int[] $ids
     * @param FeedIndexMetadata $metadata
     *
     * @return ?array
     */
    public function execute(array $ids, FeedIndexMetadata $metadata): ?array;
}
