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

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Get parent product SKUs
 */
class ParentProductsQuery
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var MetadataPool
     */
    private MetadataPool $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param array $productIds
     * @return Select
     */
    public function getQuery(array $productIds): Select
    {
        /** @var EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $connection = $this->resourceConnection->getConnection();
        $linkField = $metadata->getLinkField();

        return $connection->select()
            ->from(
                ['child_parent' => $this->resourceConnection->getTableName('catalog_product_relation')],
                [
                    'child_parent.child_id',
                ]
            )
            ->joinInner(
                ['parent_product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                \sprintf('parent_product.%1$s = child_parent.parent_id', $linkField),
                [
                    'parent_product.type_id',
                    'parent_product.sku',
                ]
            )
            ->joinInner(
                ['parent_website' => $this->resourceConnection->getTableName('catalog_product_website')],
                'parent_website.product_id = parent_product.entity_id ',
                ['parent_website.website_id']
            )
            ->where('child_parent.child_id IN (?)', $productIds);
    }
}
