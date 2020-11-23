<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogExportApi\Api;

/**
 * Rating metadata entity repository interface
 */
interface RatingMetadataRepositoryInterface
{
    /**
     * Get rating metadata by rating ids
     *
     * @param \Magento\CatalogExportApi\Api\EntityRequest $request
     *
     * @return \Magento\CatalogExportApi\Api\Data\RatingMetadata[]
     *
     * @throws \InvalidArgumentException
     */
    public function get(EntityRequest $request): array;
}
