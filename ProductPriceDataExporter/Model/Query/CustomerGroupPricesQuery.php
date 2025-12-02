<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
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
     * Get query for customer group prices
     *
     * @param array $productIds
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getQuery(array $productIds): Select
    {
        return $this->resourceConnection->getConnection()
            ->select()
            ->union([
                $this->getCustomerGroupPricesSelect($productIds),
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
                ' AND tier.all_groups = 1',
                $this->tierPriceColumns(),
            )->columns([
                'product.sku',
                'product.entity_id',
                'tier.website_id',
                'tier.customer_group_id'
            ])
            ->group('tier.website_id')
            ->group('product.entity_id')
            ->where('product.entity_id IN (?)', $productIds);
    }

    /**
     * Get tier price columns
     *
     * @return array
     */
    private function tierPriceColumns(): array
    {
        return [
            'price' => $this->resourceConnection->getConnection()->getCheckSql(
                '`tier`.percentage_value IS NULL',
                new \Zend_Db_Expr(
                    'GROUP_CONCAT(CONCAT(`tier`.`qty`, \':\', `tier`.`value`) SEPARATOR \',\')'
                ),
                'null'
            ),
            'percentage' => $this->resourceConnection->getConnection()->getCheckSql(
                '`tier`.percentage_value IS NULL',
                'null',
                new \Zend_Db_Expr(
                    'GROUP_CONCAT(CONCAT(`tier`.`qty`, \':\', `tier`.`percentage_value`) SEPARATOR \',\')'
                )
            )
        ];
    }

    /**
     * Get link field for product entity
     *
     * @return string
     * @throws \Exception
     */
    private function getLinkField(): string
    {
        /** @var \Magento\Framework\EntityManager\EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        return $metadata->getLinkField();
    }

    /**
     * Get query to retrieve customer group prices
     *
     * @param  array $productIds
     * @return Select
     * @throws \Exception
     */
    private function getCustomerGroupPricesSelect(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $linkField = $this->getLinkField();
        return $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->joinInner(
                ['tier' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')],
                \sprintf('product.%1$s = tier.%1$s', $linkField) .
                ' AND tier.all_groups = 0',
                $this->tierPriceColumns(),
            )
            ->columns([
                'sku' => 'product.sku',
                'entity_id' => 'product.entity_id',
                'website_id' => 'tier.website_id',
                'all_groups' => 'tier.all_groups',
                'customer_group_id' => 'tier.customer_group_id',
                'rule_price' => new \Zend_Db_Expr('NULL')
            ])
            ->group('website_id')
            ->group('customer_group_id')
            ->group('product.entity_id')
            ->where('product.entity_id IN (?)', $productIds)
            ->where(\sprintf('tier.%1$s IS NOT NULL', $linkField));
    }

    /**
     * Get query to retrieve catalog rule prices
     *
     * @param  array $productIds
     * @return Select
     * @throws \Exception
     */
    private function getCatalogRulePricesSelect(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->joinLeft(
                ['website' => $this->resourceConnection->getTableName('catalog_product_website')],
                'website.product_id = product.entity_id',
                []
            )
            ->joinInner(
                ['rule' => $this->resourceConnection->getTableName('catalogrule_product_price')],
                'product.entity_id = rule.product_id' .
                ' AND rule.website_id = website.website_id AND rule.rule_date = ' . $this->getWebsiteDate(),
                []
            )->columns([
                'price' => new \Zend_Db_Expr('NULL'),
                'percentage' => new \Zend_Db_Expr('NULL'),
                'sku' => 'product.sku',
                'entity_id' => 'product.entity_id',
                'website_id' =>'rule.website_id',
                'all_groups' => new \Zend_Db_Expr('0'),
                'customer_group_id' => 'rule.customer_group_id',
                'rule_price' => 'rule.rule_price'
            ])
            ->group('website_id')
            ->group('customer_group_id')
            ->group('product.entity_id')
            ->where('product.entity_id IN (?)', $productIds)
            ->where('rule.product_id IS NOT NULL');
    }

    /**
     * Get CASE SQL expression for website specific dates
     *
     * @return \Zend_Db_Expr
     */
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
