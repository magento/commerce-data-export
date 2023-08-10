<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Get raw price data for price && special_price attributes with parent product SKUs
 */
class ProductPricesQuery
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var MetadataPool
     */
    private MetadataPool $metadataPool;

    private const IGNORED_TYPES = [Configurable::TYPE_CODE, 'giftcard'];

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
     * Get query for product price
     *
     * @param array $productIds
     * @param array $priceAttributes
     * @return Select
     * @throws \Exception
     */
    public function getQuery(array $productIds, array $priceAttributes = []): Select
    {
        /** @var EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $connection = $this->resourceConnection->getConnection();
        $eavAttributeTable = $this->resourceConnection->getTableName('catalog_product_entity_decimal');
        $linkField = $metadata->getLinkField();

        return $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                [
                    'sku',
                    'entity_id',
                    'type_id'
                ]
            )
            ->joinInner(
                ['product_website' => $this->resourceConnection->getTableName('catalog_product_website')],
                'product_website.product_id = product.entity_id',
                ['website_id']
            )
            // TODO: get website code default store ID from \Magento\Store\Model\StoreManagerInterface to avoid joins
            ->joinInner(
                ['store_website' => $this->resourceConnection->getTableName('store_website')],
                'store_website.website_id = product_website.website_id',
                ['websiteCode' => 'code']
            )
            ->joinInner(
                ['store_group' => $this->resourceConnection->getTableName('store_group')],
                'store_group.website_id = store_website.website_id',
                []
            )
            ->joinLeft(
                ['eavi' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                \sprintf('product.%1$s = eavi.%1$s', $linkField) .
                $connection->quoteInto(' AND eavi.attribute_id IN (?)', $priceAttributes) .
                ' AND eavi.store_id = 0',
                ['price_type' => 'value']
            )
            ->joinLeft(
                ['eav_store' => $eavAttributeTable],
                \sprintf('product.%1$s = eav_store.%1$s', $linkField) .
                $connection->quoteInto(' AND eav_store.attribute_id IN (?)', $priceAttributes) .
                ' AND eav_store.store_id IN (store_group.default_store_id, 0)',
                [
                    'price' => 'eav_store.value',
                    'attributeId' => 'attribute_id'
                ]
            )
            // get parent skus
            ->joinLeft(
                ['child_parent' => $this->resourceConnection->getTableName('catalog_product_relation')],
                'child_parent.child_id = product.entity_id',
                []
            )
            ->joinLeft(
                ['parent_product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                \sprintf('parent_product.%1$s = child_parent.parent_id', $linkField),
                []
            )
            ->joinLeft(
                ['parent_website' => $this->resourceConnection->getTableName('catalog_product_website')],
                'parent_website.product_id = parent_product.entity_id '
                . 'AND parent_website.website_id = product_website.website_id',
                []
            )
            ->joinLeft(
                ['parent_sku' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'parent_sku.entity_id = parent_website.product_id',
                [
                    'parent_skus' => new Expression(
                        "GROUP_CONCAT(DISTINCT parent_sku.type_id,':',parent_sku.sku separator ', ')"
                    )
                ]
            )
            ->where('product.entity_id IN (?)', $productIds)
            ->where('product.type_id NOT IN (?)', self::IGNORED_TYPES)
            ->order('product.entity_id')
            ->order('product_website.website_id')
            ->order('eav_store.attribute_id')
            ->order('eav_store.store_id')
            ->group('product.entity_id')
            ->group('product_website.website_id')
            ->group('eav_store.attribute_id')
            ->group('eav_store.store_id');
    }
}
