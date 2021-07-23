<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductOverrideDataExporter\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;

class ProductOverrideQueryTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Magento\QueryXml\Model\QueryFactory
     */
    private $queryFactory;

    protected function setUp(): void
    {
        $this->queryFactory = Bootstrap::getObjectManager()->create(\Magento\QueryXml\Model\QueryFactory::class);
    }

    public function getProductOverrideQueries(): array
    {
        return [
            [
                'queryName' => 'productPriceOverrides',
                'expectedSql' => "SELECT `catalog_product_index_price`.`entity_id` AS `productId`, `catalog_product_index_price`.`customer_group_id`, SHA1(`catalog_product_index_price`.`customer_group_id`) AS `customerGroupCode`, `catalog_product_index_price`.`website_id`, `catalog_product_index_price`.`tax_class_id` AS `taxClassId`, `catalog_product_index_price`.`price` AS `minimumRegularPrice`, `catalog_product_index_price`.`final_price` AS `minimumFinalPrice`, `catalog_product_index_price`.`price` AS `maximumRegularPrice`, `catalog_product_index_price`.`final_price` AS `maximumFinalPrice`, `catalog_product_entity`.`sku`, `store_website`.`code` AS `websiteCode` "
                    . "FROM `catalog_product_index_price` "
                    . "INNER JOIN `catalog_product_entity` ON (`catalog_product_entity`.`entity_id` = `catalog_product_index_price`.`entity_id` AND `catalog_product_entity`.`type_id` != 'configurable') AND (catalog_product_entity.created_in <= 1 AND catalog_product_entity.updated_in > 1) "
                    . "LEFT JOIN `store_website` ON (`store_website`.`website_id` = `catalog_product_index_price`.`website_id`) WHERE ((`catalog_product_index_price`.`entity_id` IN(::entityIds::))) ORDER BY `catalog_product_index_price`.`customer_group_id` asc, `catalog_product_index_price`.`website_id` asc"
            ],
            [
                'queryName' => 'configurableProductPriceOverrides',
                'expectedSql' => "SELECT `catalog_product_index_price`.`customer_group_id`, `catalog_product_index_price`.`website_id`, SHA1(`catalog_product_index_price`.`customer_group_id`) AS `customerGroupCode`, MAX(`catalog_product_index_price`.`tax_class_id`) AS `taxClassId`, MIN(`catalog_product_index_price`.`price`) AS `minimumRegularPrice`, MIN(`catalog_product_index_price`.`final_price`) AS `minimumFinalPrice`, MAX(`catalog_product_index_price`.`price`) AS `maximumRegularPrice`, MAX(`catalog_product_index_price`.`final_price`) AS `maximumFinalPrice`, `parent`.`entity_id` AS `productId`, `parent`.`sku`, `store_website`.`code` AS `websiteCode` "
                    . "FROM `catalog_product_index_price` "
                    . "INNER JOIN `catalog_product_super_link` ON (`catalog_product_super_link`.`product_id` = `catalog_product_index_price`.`entity_id`) "
                    . "INNER JOIN `catalog_product_entity` AS `parent` ON (`parent`.`row_id` = `catalog_product_super_link`.`parent_id` AND ((`parent`.`type_id` = 'configurable' AND `parent`.`entity_id` IN(::entityIds::)))) AND (parent.created_in <= 1 AND parent.updated_in > 1) "
                    . "LEFT JOIN `store_website` ON (`store_website`.`website_id` = `catalog_product_index_price`.`website_id`) "
                    . "GROUP BY `catalog_product_index_price`.`customer_group_id`, `catalog_product_index_price`.`website_id`, `parent`.`entity_id` ORDER BY `catalog_product_index_price`.`customer_group_id` asc, `catalog_product_index_price`.`website_id` asc"
            ],
            [
                'queryName' => 'productCategoryPermissions',
                'expectedSql' => "SELECT `catalog_category_product`.`product_id` AS `productId`, `magento_catalogpermissions_index_product`.`customer_group_id`, SHA1(`magento_catalogpermissions_index_product`.`customer_group_id`) AS `customerGroupCode`, `magento_catalogpermissions_index_product`.`grant_catalog_category_view` AS `displayable`, `magento_catalogpermissions_index_product`.`grant_checkout_items` AS `buyable`, `magento_catalogpermissions_index_product`.`grant_catalog_product_price` AS `canDisplayPrice`, `store_website`.`code` AS `websiteCode` "
                    . "FROM `catalog_category_product`" . "INNER JOIN `magento_catalogpermissions_index_product` ON (`magento_catalogpermissions_index_product`.`product_id` = `catalog_category_product`.`product_id`)" . "INNER JOIN `store` ON (`store`.`store_id` = `magento_catalogpermissions_index_product`.`store_id`)" . "INNER JOIN `store_website` ON (`store_website`.`website_id` = `store`.`website_id`) "
                    . "WHERE ((`catalog_category_product`.`entity_id` IN(::entityIds::))) "
                    . "ORDER BY `catalog_category_product`.`product_id` asc, `magento_catalogpermissions_index_product`.`customer_group_id` asc"
            ]
        ];
    }

    /**
     * @param $queryName
     * @param $file
     * @dataProvider getProductOverrideQueries
     */
    public function testProductOverrideQueries($queryName, $expectedSql)
    {
        /** @var \Magento\Framework\App\ResourceConnection $resource */
        $resource = Bootstrap::getObjectManager()->create(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        if ($connection->isTableExists('staging_update')) {
            $sql = $this->queryFactory->create($queryName)->getSelect()->assemble();
            $actualSql = trim(str_replace(PHP_EOL, "", preg_replace("!\s+!", " ", $sql)));
            self::assertEquals($expectedSql, $actualSql);
        }
    }
}
