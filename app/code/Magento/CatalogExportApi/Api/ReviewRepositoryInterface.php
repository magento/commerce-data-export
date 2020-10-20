<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogExportApi\Api;

/**
 * Review entity repository interface
 */
interface ReviewRepositoryInterface
{
    /**
     * Get categories by ids
     *
     * @param \Magento\CatalogExportApi\Api\EntityRequest $request
     *
     * @return \Magento\CatalogExportApi\Api\Data\Review[]
     */
    public function get(EntityRequest $request);
}
