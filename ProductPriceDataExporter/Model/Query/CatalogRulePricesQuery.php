<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Get catalog rule prices
 */
class CatalogRulePricesQuery
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @inheritDoc
     * @throws UnableRetrieveData
     */
    public function getQuery(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        /** @var \Magento\Framework\EntityManager\EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);

        $linkField = $metadata->getLinkField();

        return $connection->select()
            ->joinInner(
                ['product_website' => $this->resourceConnection->getTableName('catalog_product_website')],
                'product_website.product_id = product.entity_id',
                ['website_id']
            )
            ->joinInner(
                ['pwi' => $this->resourceConnection->getTableName('catalog_product_index_website')],
                'pwi.website_id = product_website.website_id',
                []
            )
            ->joinInner(
                ['rule' => $this->resourceConnection->getTableName('catalogrule_product_price')],
                \sprintf('product.%1$s = rule.product_id', $linkField) .
                ' AND rule.website_id = product_website.website_id AND rule.rule_date = pwi.website_date',
                [
                    'rule_price AS value',
                    'customer_group_id',
                ]
            )
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                [
                    'sku',
                    'entity_id',
                    'type_id',
                ]
            )
            ->where('product.entity_id IN (?)', $productIds);
    }
}
