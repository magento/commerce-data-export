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
                $this->getCustomerGroupWithCatalogRulePricesSelect($productIds)
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
                [
                    'price' => new \Zend_Db_Expr(
                        'GROUP_CONCAT(CONCAT(`tier`.`qty`, \':\', `tier`.`value`) SEPARATOR \',\')'
                    ),
                    'percentage' => new \Zend_Db_Expr(
                        'GROUP_CONCAT(CONCAT(`tier`.`qty`, \':\', `tier`.`percentage_value`) SEPARATOR \',\')'
                    )
                ]
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
     * @param  array $productIds
     * @return Select
     * @throws \Exception
     */
    private function getCustomerGroupWithCatalogRulePricesSelect(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $linkField = $this->getLinkField();
        return $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->joinLeft(
                ['tier' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')],
                \sprintf('product.%1$s = tier.%1$s', $linkField) .
                ' AND tier.all_groups = 0',
                [
                    'price' => new \Zend_Db_Expr(
                        'GROUP_CONCAT(CONCAT(`tier`.`qty`, \':\', `tier`.`value`) SEPARATOR \',\')'
                    ),
                    'percentage' => new \Zend_Db_Expr(
                        'GROUP_CONCAT(CONCAT(`tier`.`qty`, \':\', `tier`.`percentage_value`) SEPARATOR \',\')'
                    )
                ]
            )
            ->joinLeft(
                ['rule' => $this->resourceConnection->getTableName('catalogrule_product_price')],
                'product.entity_id = rule.product_id' .
                ' AND (rule.website_id = tier.website_id OR tier.website_id IS NULL)' .
                ' AND (rule.customer_group_id = tier.customer_group_id OR tier.customer_group_id IS NULL)' .
                ' AND rule.rule_date = ' . $this->getWebsiteDate(),
                []
            )->columns([
                'product.sku',
                'product.entity_id',
                'website_id' => new \Zend_Db_Expr(
                    'COALESCE(`tier`.`website_id`, `rule`.`website_id`)'
                ),
                'tier.all_groups',
                'customer_group_id' => new \Zend_Db_Expr(
                    'COALESCE(`tier`.`customer_group_id`, `rule`.`customer_group_id`)'
                ),
                'rule.rule_price'
            ])
            ->group('website_id')
            ->group('customer_group_id')
            ->group('product.entity_id')
            ->where('product.entity_id IN (?)', $productIds)
            ->where(\sprintf('tier.%1$s IS NOT NULL', $linkField)
                . ' OR rule.product_id IS NOT NULL');
    }

    /**
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
