<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Get raw price for customer group price
 */
class CustomerGroupPricesQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var DateWebsiteProvider
     */
    private DateWebsiteProvider $dateWebsiteProvider;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @param DateWebsiteProvider $dateWebsiteProvider
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        DateWebsiteProvider $dateWebsiteProvider
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->dateWebsiteProvider = $dateWebsiteProvider;
    }

    /**
     * @param array $productIds
     * @return Select
     */
    public function getQuery(array $productIds): Select
    {
        return $this->resourceConnection->getConnection()
            ->select()
            ->union([
                $this->getCustomerGroupWithCatalogRulePricesSelect($productIds),
                $this->getCatalogRulePricesSelect($productIds)
            ], Select::SQL_UNION_ALL);
    }

    /**
     * Get query to retrieve customer group prices for fallback items
     *
     * @param array $productIds
     * @return Select
     * @throws \Exception
     */
    public function getCustomerGroupFallbackQuery(array $productIds): Select
    {
        return $this->resourceConnection->getConnection()->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->joinInner(
                ['tier' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')],
                \sprintf('product.%1$s = tier.%1$s', $this->getLinkField()) .
                ' AND tier.all_groups = 1 AND tier.qty=1 ',
                []
            )->columns([
                'product.sku',
                'product.entity_id',
                'tier.website_id',
                'tier.value as group_price',
                'tier.percentage_value',
                'tier.customer_group_id'
            ])
            ->where('product.entity_id IN (?)', $productIds);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getLinkField(): string
    {
        /** @var \Magento\Framework\EntityManager\EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        return $metadata->getLinkField();
    }

    private function getCustomerGroupWithCatalogRulePricesSelect(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->joinInner(
                ['tier' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')],
                \sprintf('product.%1$s = tier.%1$s', $this->getLinkField()) .
                ' AND tier.qty=1 AND tier.all_groups = 0',
                []
            )
            ->joinLeft(
                ['rule' => $this->resourceConnection->getTableName('catalogrule_product_price')],
                'product.entity_id = rule.product_id' .
                ' AND rule.website_id = tier.website_id' .
                ' AND rule.customer_group_id = tier.customer_group_id' .
                ' AND rule.rule_date = ' . $this->getWebsiteDate(),
                []
            )->columns([
                'product.sku',
                'product.entity_id',
                'tier.website_id',
                'tier.all_groups',
                'tier.value as group_price',
                'tier.percentage_value',
                'tier.customer_group_id',
                'rule.rule_price'
            ])
            ->where('product.entity_id IN (?)', $productIds);
    }

    private function getCatalogRulePricesSelect(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->joinInner(
                ['rule' => $this->resourceConnection->getTableName('catalogrule_product_price')],
                'product.entity_id = rule.product_id' .
                ' AND rule.rule_date = ' . $this->getWebsiteDate(),
                []
            )
            ->joinLeft(
                ['tier' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')],
                \sprintf('product.%1$s = tier.%1$s', $this->getLinkField()) .
                ' AND tier.qty=1 AND tier.all_groups = 0 AND tier.customer_group_id = rule.customer_group_id',
                []
            )
            ->columns([
                'product.sku',
                'product.entity_id',
                'rule.website_id',
                'tier.all_groups',
                'tier.value as group_price',
                'tier.percentage_value',
                'rule.customer_group_id',
                'rule.rule_price'
            ])
            ->where('product.entity_id IN (?)', $productIds)
            ->where('tier.value_id is NULL');
    }

    private function getWebsiteDate(): \Zend_Db_Expr
    {
        $caseResults = [];
        foreach ($this->dateWebsiteProvider->getWebsitesDate() as $websiteId => $date) {
            $caseResults["rule.website_id = '$websiteId'"] = "'$date'";
        }
        return $this->resourceConnection->getConnection()->getCaseSql(
            '',
            $caseResults,
            'CURRENT_DATE'
        );
    }
}
