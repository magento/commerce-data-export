<?php
/**
 * Copyright 2021 Adobe
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
