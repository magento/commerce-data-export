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
     * @var array
     */
    private $cache = [];

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
    private function getUrlPathAttributeId() : int
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
                ->where('a.attribute_code = ?', 'url_path')
        );
    }

    /**
     * Get resource table for attribute
     * @param string $tableName
     * @return string
     */
    private function getAttributeTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName([$tableName, 'varchar']);
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
        $productIds = $arguments['productId'] ?? [];
        $connection = $this->resourceConnection->getConnection();

        if (isset($this->cache[$storeViewCode])) {
            extract($this->cache[$storeViewCode], EXTR_SKIP);
        } else {
            $categoryEntityTableName = $this->getTable($this->mainTable);
            $joinField = $connection->getAutoIncrementField($categoryEntityTableName);
            $storeId = $this->getStoreId($storeViewCode);
            $attributeId = $this->getUrlPathAttributeId();
            $categoryProductIndexTableName = $this->getIndexTableName($storeId);
            $categoryAttributeTableName = $this->getAttributeTable($this->mainTable);
            $this->cache[$storeViewCode] = compact(
                'categoryEntityTableName',
                'joinField',
                'storeId',
                'attributeId',
                'categoryProductIndexTableName',
                'categoryAttributeTableName'
            );
        }

        $select = $connection->select()
            ->from(
                ['ccp' => $categoryProductIndexTableName],
                [
                    'productId' => 'ccp.product_id',
                    'categoryId' => 'ccp.category_id',
                    'productPosition' => 'ccp.position',
                ]
            )
            ->join(
                ['cce' => $categoryEntityTableName],
                'ccp.category_id = cce.entity_id',
                []
            );

        $defaultValueTableAlias = 'url_key_default';
        $storeValueTableAlias = 'url_key_store';
        $defaultValueJoinCondition = sprintf(
            '%1$s.%2$s = cce.%2$s AND %1$s.attribute_id = %3$d AND %1$s.store_id = 0',
            $defaultValueTableAlias,
            $joinField,
            $attributeId
        );
        $storeViewValueJoinCondition = sprintf(
            '%1$s.%2$s = cce.%2$s AND %1$s.attribute_id = %3$d AND %1$s.store_id = %4$d',
            $storeValueTableAlias,
            $joinField,
            $attributeId,
            $storeId
        );
        $select
            ->joinRight(
                [$defaultValueTableAlias => $categoryAttributeTableName],
                $defaultValueJoinCondition,
                []
            )
            ->joinLeft(
                [$storeValueTableAlias => $categoryAttributeTableName],
                $storeViewValueJoinCondition,
                []
            )
            ->where('ccp.product_id IN (?)', $productIds)
            ->having('categoryPath IS NOT NULL')
            ->columns(
                [
                    'categoryPath' => new ColumnValueExpression(
                        sprintf('IFNULL(%1$s.value, %2$s.value)', $storeValueTableAlias, $defaultValueTableAlias)
                    )
                ]
            );
        return $select;
    }

    /**
     * @param string $storeViewCode
     * @return Int
     */
    private function getStoreId(string $storeViewCode) : Int
    {
        $connection = $this->resourceConnection->getConnection();
        return (int) $connection->fetchOne(
            $connection->select()
                ->from(['store' => $this->getTable('store')],'store_id')
                ->where('store.code = ?', $storeViewCode)
        );
    }

    /**
     * Returns name of catalog_category_product_index table based on currently used dimension.
     *
     * @param int $storeId
     * @return string
     */
    private function getIndexTableName(int $storeId) : String
    {
        $catalogCategoryProductDimension = new Dimension(
            \Magento\Store\Model\Store::ENTITY,
            $storeId
        );

        return $this->tableResolver->resolve(
            AbstractAction::MAIN_INDEX_TABLE,
            [$catalogCategoryProductDimension]
        );
    }
}
