<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Interface for IDs resolvers.
 * Each resolver returns IDs for entities lined with initial entity IDs, such as parent to child relationship etc.
 * @see AffectedIdsResolverPool for usage examples
 */
interface AffectedIdsResolverInterface
{
    /**
     * Returns IDs that are affected by the change of entities that provided IDs as an input.
     *
     * @param string[] $ids
     * @return string[]
     */
    public function getAllAffectedIds(array $ids): array;
}
