<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
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

    /**
     * @var Config
     */
    private Config $eavConfig;

    /**
     * @var array|null
     */
    private ?array $priceAttributes = null;

    private const IGNORED_TYPES = [Configurable::TYPE_CODE, Type::TYPE_BUNDLE];

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @param Config $eavConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        Config $eavConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @inheritDoc
     * @throws UnableRetrieveData
     */
    public function getQuery(array $productIds): Select
    {
        /** @var \Magento\Framework\EntityManager\EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $connection = $this->resourceConnection->getConnection();


        $eavAttributeTable = $this->resourceConnection->getTableName('catalog_product_entity_decimal');
        $linkField = $metadata->getLinkField();

        if ($this->priceAttributes === null) {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'price');
            if ($attribute) {
                $this->priceAttributes['price'] = $attribute->getId();
            }
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'special_price');
            if ($attribute) {
                $this->priceAttributes['special_price'] = $attribute->getId();
            }
        }
        if (!$this->priceAttributes) {
            throw new UnableRetrieveData('Price attributes not found');
        }

        $priceAttributeCondition = \sprintf(
            "CASE WHEN eav.attribute_id = %s THEN 'price'
            WHEN eav.attribute_id = %s THEN 'special_price' ELSE 'price' END",
            $this->priceAttributes['price'],
            $this->priceAttributes['special_price']
        );

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
                ['sg' => $this->resourceConnection->getTableName('store_group')],
                'sg.website_id = store_website.website_id',
                []
            )
            ->joinLeft(
                ['eav' => $eavAttributeTable],
                \sprintf('product.%1$s = eav.%1$s', $linkField) .
                $connection->quoteInto(' AND eav.attribute_id IN (?)', \array_values($this->priceAttributes)) .
                ' AND eav.store_id = 0',
                ['attribute_id']
            )
            ->joinLeft(
                ['eav_store' => $eavAttributeTable],
                \sprintf('product.%1$s = eav_store.%1$s', $linkField) .
                ' AND eav_store.store_id = sg.default_store_id AND eav_store.attribute_id = eav.attribute_id',
                ['price' => new Expression(
                    'CASE WHEN eav_store.value_id IS NOT NULL THEN eav_store.value WHEN eav.value '
                    . 'IS NOT NULL THEN eav.value ELSE 0 END'
                )]
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
            ->columns(['price_attribute' => new Expression($priceAttributeCondition)])
            ->where('product.entity_id IN (?)', $productIds)
            ->where('product.type_id NOT IN (?)', self::IGNORED_TYPES)
            ->group('product.entity_id')
            ->group('product_website.website_id')
            ->group('eav.attribute_id');
    }
}
