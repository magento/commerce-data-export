<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExportApi\Api;

/**
 * Product variant repository
 */
interface ProductVariantRepositoryInterface
{
    /**
     * Get products variants by ids
     *
     * @param string[] $ids
     * @return \Magento\CatalogExportApi\Api\Data\ProductVariant[]
     */
    public function get(array $ids): array;

    /**
     * Get products variants by product ids
     *
     * @param string[] $productIds
     * @return \Magento\CatalogExportApi\Api\Data\ProductVariant[]
     */
    public function getByProductIds(array $productIds): array;
}
