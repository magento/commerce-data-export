<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogExportApi\Api;

/**
 * Product entity repository
 */
interface ProductRepositoryInterface
{
    /**
     * Get products by ids
     *
     * @param \Magento\CatalogExportApi\Api\EntityRequest $request
     *
     * @return \Magento\CatalogExportApi\Api\Data\Product[]
     */
    public function get(EntityRequest $request);
}
