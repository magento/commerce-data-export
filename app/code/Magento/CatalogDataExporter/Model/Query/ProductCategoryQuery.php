<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\ColumnValueExpression;

/**
 * Product category query for catalog data exporter
 */
class ProductCategoryQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

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
        string $mainTable = 'catalog_category_entity'
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->mainTable = $mainTable;
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
     * Get URL key attribute ID
     *
     * @return array
     */
    private function getUrlKeyAttributeId() : int
    {
        $connection = $this->resourceConnection->getConnection();
        return (int)$connection->fetchOne(
            $connection->select()
                ->from(['a' => $this->getTable('eav_attribute')], ['attribute_id'])
                ->join(
                    ['t' => $this->getTable('eav_entity_type')],
                    't.entity_type_id = a.entity_type_id',
                    []
                )
                ->where('t.entity_table = ?', $this->mainTable)
                ->where('a.attribute_code = ?', 'url_key')
        );
    }

    /**
     * Get resource table for attribute
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
     * Get query for provider
     *
     * @param array $arguments
     * @return Select
     */
    public function getQuery(array $arguments) : Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCodes = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['ccp' => $this->resourceConnection->getTableName('catalog_category_product')],
                [
                    'productId' => 'ccp.product_id',
                ]
            )
            ->joinCross(
                ['s' => $this->getTable('store')],
                ['storeViewCode' => 's.code']
            )
            ->join(
                ['cce' => $this->resourceConnection->getTableName('catalog_category_entity')],
                'cce.entity_id = ccp.category_id',
                []
            )
            ->join(
                ['cpath' => $this->resourceConnection->getTableName('catalog_category_entity')],
                "find_in_set(cpath.entity_id, replace(cce.path, '/', ','))",
                []
            );

        $attributeId = $this->getUrlKeyAttributeId();
        $joinField = $connection->getAutoIncrementField($this->getTable($this->mainTable));
        $defaultValueTableAlias = 'url_key_default';
        $storeValueTableAlias = 'url_key_store';
        $defaultValueJoinCondition = sprintf(
            '%1$s.%2$s = cpath.%2$s AND %1$s.attribute_id = %3$d AND %1$s.store_id = 0',
            $defaultValueTableAlias,
            $joinField,
            $attributeId
        );
        $storeViewValueJoinCondition = sprintf(
            '%1$s.%2$s = cpath.%2$s AND %1$s.attribute_id = %3$d AND %1$s.store_id = s.store_id',
            $storeValueTableAlias,
            $joinField,
            $attributeId
        );
        $attributeValueExpression = sprintf(
            'CASE WHEN %1$s.value IS NULL THEN %2$s.value ELSE %1$s.value END',
            $storeValueTableAlias,
            $defaultValueTableAlias
        );
        $select
            ->joinLeft(
                [
                    $defaultValueTableAlias => $this->getAttributeTable($this->mainTable, 'varchar')
                ],
                $defaultValueJoinCondition,
                []
            )
            ->joinLeft(
                [
                    $storeValueTableAlias => $this->getAttributeTable($this->mainTable, 'varchar')
                ],
                $storeViewValueJoinCondition,
                []
            )
            ->group(['product_id', 'category_id', 's.code'])
            ->where('ccp.product_id IN (?)', $productIds)
            ->where('s.store_id != 0')
            ->where('s.code IN (?)', $storeViewCodes)
            ->columns(
                [
                    'categories' => new ColumnValueExpression(
                        sprintf(
                            "group_concat(%s order by cpath.level separator  '/' )",
                            $attributeValueExpression
                        )
                    )
                ]
            );
        return $select;
    }
}
