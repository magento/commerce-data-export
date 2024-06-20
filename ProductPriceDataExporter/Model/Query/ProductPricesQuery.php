<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Eav\Model\Config;

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
    private Config $eavConfig;
    private int|bool|null $priceType = null;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @param Config|null $eavConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        Config $eavConfig = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->eavConfig = $eavConfig ?? ObjectManager::getInstance()->get(Config::class);
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
        $linkField = $metadata->getLinkField();

        $select = $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                [
                    'sku',
                    'type_id',
                    'entity_id',
                ]
            )
            ->where('product.entity_id IN (?)', $productIds);

        $eavAttributeTable = $this->resourceConnection->getTableName('catalog_product_entity_decimal');
        foreach ($priceAttributes as $attributeId => $attributeCode) {
            $alias = 'eav_' . $attributeCode;
            $select->joinLeft(
                [$alias => $eavAttributeTable],
                \sprintf('product.%1$s = %2$s.%1$s', $linkField, $alias) .
                $connection->quoteInto(" AND $alias.attribute_id = ?", $attributeId),
                [
                    $attributeCode => 'value',
                    $attributeCode . '_storeId' => 'store_id',
                ]
            );
        }

        if ($this->getPriceTypeAttributeId()) {
            $select->joinLeft(
                ['price_type' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                \sprintf('product.%1$s = price_type.%1$s', $linkField) .
                $connection->quoteInto(' AND price_type.attribute_id = ?', $this->getPriceTypeAttributeId()) .
                ' AND price_type.store_id = 0',
                ['price_type' => 'value']
            );
        }
        return $select;
    }

    /**
     * Fetch the websites associated with the given product IDs.
     *
     * @param array $productIds
     * @return Select
     */
    public function getProductWebsiteAssociations(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();

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
            ->joinInner(
                ['store_website' => $this->resourceConnection->getTableName('store_website')],
                'store_website.website_id = product_website.website_id',
                ['websiteCode' => 'code']
            )
            ->where('product.entity_id IN (?)', $productIds)
            ->where('product.type_id NOT IN (?)', self::IGNORED_TYPES);
    }

    /**
     * @return Select
     */
    public function getWebsitesQuery(): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(
                ['s' => $this->resourceConnection->getTableName('store')],
                ['store_id']
            )->joinInner(
                ['w' => $this->resourceConnection->getTableName('store_website')],
                's.website_id = w.website_id',
                ['code', 'website_id']
            );
    }

    /**
     * Get price type attribute id
     * @return int|bool
     */
    private function getPriceTypeAttributeId(): int|bool
    {
        if ($this->priceType === null) {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'price_type');
            if ($attribute) {
                $this->priceType = (int)$attribute->getId();
            } else {
                $this->priceType = false;
            }
        }
        return $this->priceType;
    }
}
