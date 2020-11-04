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
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField($this->getTable('catalog_product_entity'));

        $select = $connection->select()
            ->from(['cpsl' => $this->getTable('catalog_product_super_link')])
            ->joinInner(
                ['cpe' => $this->getTable('catalog_product_entity')],
                sprintf('cpe.%1$s = cpsl.parent_id', $joinField)
            )
            ->columns(['productId' => 'cpsl.product_id', 'sku' => 'cpe.sku', 'productType' => 'cpe.type_id'])
            ->where('cpsl.product_id IN (?)', $productIds);
        return $select;
    }
}
