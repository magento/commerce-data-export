<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\CatalogDataExporter\Model\StoreIdResolver;
use Magento\Framework\App\ObjectManager;
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
     * @var StoreIdResolver
     */
    private $storeIdResolver;

    /**
     * ProductCategoryIdsQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param TableResolver $tableResolver
     * @param string $mainTable
     * @param StoreIdResolver|null $storeIdResolver
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TableResolver $tableResolver,
        string $mainTable = 'catalog_category_entity',
        ?StoreIdResolver $storeIdResolver = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tableResolver = $tableResolver;
        $this->mainTable = $mainTable;
        $this->storeIdResolver = $storeIdResolver ?? ObjectManager::getInstance()->get(StoreIdResolver::class);
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
            $categoryProductIndexTableName = $this->getIndexTableName(
                $this->storeIdResolver->getStoreId($storeViewCode)
            );
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
