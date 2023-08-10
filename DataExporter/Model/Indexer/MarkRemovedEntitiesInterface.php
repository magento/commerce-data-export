<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
