<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Product attribute query for catalog data exporter
 */
class ProductAttributeQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array
     */
    private $attributeTypes = ['int', 'varchar', 'decimal', 'text', 'datetime'];

    /**
     * @var string
     */
    private $mainTable;

    /**
     * MainProductQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param string $mainTable
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $mainTable = 'catalog_product_entity'
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->mainTable = $mainTable;
    }

    /**
     * Get resource table for attributes
     *
     * @param string $tableName
     * @param string $type
     * @return string
     */
    private function getAttributeTable(string $tableName, string $type) : string
    {
        return $this->resourceConnection->getTableName([$tableName, $type]);
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
     * Get user defined attributes ids from EAV
     *
     * @return array
     */
    private function getUserDefinedAttributeIds() : array
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->fetchCol(
            $connection->select()
                ->from(['a' => $this->getTable('eav_attribute')], [])
                ->join(
                    ['t' => $this->getTable('eav_entity_type')],
                    't.entity_type_id = a.entity_type_id',
                    []
                )
                ->where('a.is_user_defined  = 1')
                ->where('t.entity_table = ?', $this->mainTable)
                ->where('a.backend_type != ?', 'static')
                ->columns(
                    [
                        'id' => 'a.attribute_id',
                    ]
                )
        );
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getQuery(array $arguments) : Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCode = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField($this->getTable($this->mainTable));

        $userDefinedAttributeIds = $this->getUserDefinedAttributeIds();

        $selects = [];
        foreach ($this->attributeTypes as $type) {
            $selects[$type] = $connection->select()
                ->from(['cpe' => $this->getTable($this->mainTable)], [])
                ->join(
                    ['s' => $this->getTable('store')],
                    '1 = 1',
                    ['storeViewCode' => 's.code']
                )
                ->join(
                    ['cpa' => $this->getAttributeTable($this->mainTable, $type)],
                    sprintf(
                        'cpa.%1$s = cpe.%1$s AND cpa.attribute_id IN (%2$s) AND cpa.store_id = s.store_id',
                        $joinField,
                        implode(',', $userDefinedAttributeIds)
                    ),
                    []
                )
                ->join(
                    ['a' => $this->getTable('eav_attribute')],
                    'a.attribute_id = cpa.attribute_id',
                    []
                )
                ->columns(
                    [
                        'productId' => 'cpe.entity_id',
                        'sku' => 'cpe.sku',
                        'storeViewCode' => 's.code',
                        'attributeCode' => 'a.attribute_code',
                        'frontendInput' => 'a.frontend_input',
                        'value' => 'cpa.value'
                    ]
                )
                ->where('s.code IN (?)', ['admin', $storeViewCode])
                ->where('cpe.entity_id IN (?)', $productIds);
        }

        return $connection->select()->union($selects, Select::SQL_UNION_ALL);
    }
}
