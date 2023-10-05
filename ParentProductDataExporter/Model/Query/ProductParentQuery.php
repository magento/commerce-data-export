<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ParentProductDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Product parent query for catalog data exporter
 */
class ProductParentQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * MainProductQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @return Select
     */
    public function getQuery(array $arguments) : Select
    {
        $productIds = $arguments['productId'] ?? [];
        $storeViewCodes = $arguments['storeViewCode'] ?? [];

        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField($this->getTable('catalog_product_entity'));

        $select = $connection->select()
            ->from(
                ['cpr' => $this->getTable('catalog_product_relation')],
                []
            )
            ->joinInner(
                ['s' => $this->getTable('store')],
                '',
                ['s.code AS storeViewCode']
            )
            ->joinInner(
                ['product_cpw' => $this->getTable('catalog_product_website')],
                'product_cpw.website_id = s.website_id AND product_cpw.product_id = cpr.child_id',
                []
            )
            ->joinInner(
                ['parent_cpe' => $this->getTable('catalog_product_entity')],
                sprintf('parent_cpe.%1$s = cpr.parent_id', $joinField),
                []
            )
            ->joinInner(
                ['parent_cpw' => $this->getTable('catalog_product_website')],
                'parent_cpw.website_id = product_cpw.website_id'
                    . ' AND parent_cpw.product_id = parent_cpe.entity_id',
                []
            )
            ->joinInner(
                ['cpe' => $this->getTable('catalog_product_entity')],
                sprintf('cpe.%1$s = cpr.parent_id', $joinField),
                []
            )
            ->columns(
                [
                    'productId' => 'cpr.child_id',
                    'sku' => 'cpe.sku',
                    'productType' => 'cpe.type_id'
                ]
            )
            ->where('cpr.child_id IN (?)', $productIds);
        if (!empty($storeViewCodes)) {
            $select->where('s.code IN (?)', $storeViewCodes);
        }
        return $select;
    }
}
