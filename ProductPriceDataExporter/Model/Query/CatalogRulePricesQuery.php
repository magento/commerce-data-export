<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Get catalog rule prices
 */
class CatalogRulePricesQuery
{
    private ResourceConnection $resourceConnection;

    private DateWebsiteProvider $dateWebsiteProvider;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DateWebsiteProvider $dateWebsiteProvider
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dateWebsiteProvider = $dateWebsiteProvider;
    }

    /**
     * @param array $productIds
     * @return Select
     */
    public function getQuery(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->joinInner(
                ['product_website' => $this->resourceConnection->getTableName('catalog_product_website')],
                'product_website.product_id = product.entity_id',
                ['website_id']
            )
            ->joinInner(
                ['rule' => $this->resourceConnection->getTableName('catalogrule_product_price')],
                'product.entity_id = rule.product_id' .
                ' AND rule.website_id = product_website.website_id AND rule.rule_date = ' . $this->getWebsiteDate(),
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

    private function getWebsiteDate(): \Zend_Db_Expr
    {
        $caseResults = [];
        foreach ($this->dateWebsiteProvider->getWebsitesDate() as $websiteId => $date) {
            $caseResults["product_website.website_id = '$websiteId'"] = "'$date'";
        }
        return $this->resourceConnection->getConnection()->getCaseSql(
            '',
            $caseResults,
            'CURRENT_DATE'
        );
    }
}
