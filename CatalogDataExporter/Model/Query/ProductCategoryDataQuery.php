<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
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
            ['categoryEntityTableName' => $categoryEntityTableName,
                'categoryProductIndexTableName' => $categoryProductIndexTableName] = $this->cache[$storeViewCode];
        } else {
            $categoryEntityTableName = $this->getTable($this->mainTable);
            $categoryProductIndexTableName = $this->getIndexTableName($this->getStoreId($storeViewCode));
            $this->cache[$storeViewCode] = compact(
                'categoryEntityTableName',
                'categoryProductIndexTableName'
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
                'ccp.category_id = cce.entity_id AND cce.level > 1',
                [
                    'path' => 'path'
                ]
            )
            ->where('ccp.product_id IN (?)', $productIds);

        return $select;
    }

    /**
     * Returns the store_id for the given store view code.
     *
     * @param string $storeViewCode
     * @return int
     */
    private function getStoreId(string $storeViewCode) : int
    {
        $connection = $this->resourceConnection->getConnection();
        return (int) $connection->fetchOne(
            $connection->select()
                ->from(['store' => $this->getTable('store')], 'store_id')
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
