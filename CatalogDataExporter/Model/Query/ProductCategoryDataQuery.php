<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\ColumnValueExpression;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver as TableResolver;
use Magento\Framework\Search\Request\Dimension;

/**
 * Product category query for catalog data exporter
 */
class ProductCategoryDataQuery
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
     * @var TableResolver
     */
    private $tableResolver;

    /**
     * ProductCategoryIdsQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param TableResolver $tableResolver
     * @param string $mainTable
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TableResolver $tableResolver,
        string $mainTable = 'catalog_category_entity'
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tableResolver = $tableResolver;
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
     * @return int
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
     * Get Root Category Ids
     *
     * @param string $storeViewCode
     * @return int[]
     */
    private function getRootCategoryIds(string $storeViewCode) : array
    {
        $rootIds = [0];
        $connection = $this->resourceConnection->getConnection();
        $rows = $connection->fetchCol(
            $connection->select()
                ->from(
                    ['store' => $this->getTable('store')],
                    []
                )
                ->join(
                    ['store_group' => $this->getTable('store_group')],
                    'store.group_id = store_group.group_id',
                    ['root_category_id']
                )
                ->where('store.store_id != 0')
                ->where('store.code = ?', $storeViewCode)
        );

        if ($rows) {
            foreach ($rows as $row) {
                $rootIds[] = (int)$row;
            }
        }

        return $rootIds;
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
     * @param string $storeViewCode
     * @return Select
     */
    public function getQuery(array $arguments, string $storeViewCode) : Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['ccp' => $this->getIndexTableName($storeViewCode)],
                [
                    'productId' => 'ccp.product_id',
                    'categoryId' => 'ccp.category_id',
                    'productPosition' => 'ccp.position',
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
            ->where('s.code IN (?)', $storeViewCode)
            ->where('ccp.category_id NOT IN (?)', $this->getRootCategoryIds($storeViewCode))
            ->columns(
                [
                    'categoryPath' => new ColumnValueExpression(
                        sprintf(
                            "group_concat(%s order by cpath.level separator  '/' )",
                            $attributeValueExpression
                        )
                    )
                ]
            );
        return $select;
    }

    /**
     * Returns name of catalog_category_product_index table based on currently used dimension.
     *
     * @param string $storeViewCode
     * @return string
     */
    private function getIndexTableName(string $storeViewCode) : String
    {
        $connection = $this->resourceConnection->getConnection();
        $storeId = $connection->fetchOne(
            $connection->select()
                ->from(['store' => $this->getTable('store')],'store_id')
                ->where('store.code = ?', $storeViewCode)
        );
        $catalogCategoryProductDimension = new Dimension(
            \Magento\Store\Model\Store::ENTITY,
            $storeId
        );

        $tableName = $this->tableResolver->resolve(
            AbstractAction::MAIN_INDEX_TABLE,
            [$catalogCategoryProductDimension]
        );

        return $tableName;
    }
}
