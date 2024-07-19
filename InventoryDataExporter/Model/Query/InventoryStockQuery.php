<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

/**
 * Inventory Stock data query builder
 */
class InventoryStockQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    private const DEFAULT_STOCK_SOURCE = 'default';

    /**
     * @param ResourceConnection $resourceConnection
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName): string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get query for provider
     *
     * @param array $productIds
     * @return Select|null
     * @throws \Zend_Db_Select_Exception
     */
    public function getQuery(array $productIds): ?Select
    {
        $connection = $this->resourceConnection->getConnection();
        $selects = [];
        foreach ($this->getStocks() as $stockId) {
            $stockId = (int)$stockId;
            if ($this->defaultStockProvider->getId() === $stockId) {
                continue;
            }
            $select = $connection->select()
                ->from(['product' => $this->resourceConnection->getTableName('catalog_product_entity')], [])
                ->joinInner(
                    [
                        'isi' => $this->getTable(sprintf('inventory_stock_%s', $stockId)),
                    ],
                    'isi.sku = product.sku',
                    []
                )->joinLeft(
                    [
                        'stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item'),
                    ],
                    'stock_item.product_id = product.entity_id',
                    []
                )->where('product.entity_id IN (?)', $productIds)
                ->columns(
                    [
                        'qty' => "isi.quantity",
                        'isSalable' => "isi.is_salable",
                        'productId' => "product.entity_id",
                        'sku' => "isi.sku",
                        'stockId' => new Expression($stockId),
                        'manageStock' => $connection->getCheckSql(
                            'stock_item.manage_stock IS NULL', 1, 'stock_item.manage_stock'
                        ),
                        'useConfigManageStock' => $connection->getCheckSql(
                            'stock_item.use_config_manage_stock IS NULL', 1, 'stock_item.use_config_manage_stock'
                        ),
                        'backorders' => $connection->getCheckSql(
                            'stock_item.backorders IS NULL', 0, 'stock_item.backorders'
                        ),
                        'useConfigBackorders' => $connection->getCheckSql(
                            'stock_item.use_config_backorders IS NULL', 1, 'stock_item.use_config_backorders'
                        ),
                        'useConfigMinQty' => $connection->getCheckSql(
                            'stock_item.use_config_min_qty IS NULL', 1, 'stock_item.use_config_min_qty'
                        ),
                        'minQty' => $connection->getCheckSql(
                            'stock_item.min_qty IS NULL', 0, 'stock_item.min_qty'
                        ),
                    ]
                );

            $selects[] = $select;
        }
        return $selects ? $connection->select()->union($selects, Select::SQL_UNION_ALL) : null;
    }

    /**
     * Get data for default stock: "inventory_stock_1" is a view used as a fallback
     *
     * @param array $productIds
     * @return Select
     * @see \Magento\InventoryCatalog\Setup\Patch\Schema\CreateLegacyStockStatusView
     */
    public function getQueryForDefaultStock(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $stockId = $this->defaultStockProvider->getId();
        return $connection->select()
            ->from(['isi' => $this->getTable(sprintf('inventory_stock_%s', $stockId))], [])
            ->where('isi.product_id IN (?)', $productIds)
            ->joinInner(
                [
                    'stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item'),
                ],
                'stock_item.product_id = isi.product_id',
                []
            )->joinInner(
                [
                    'source_item' => $this->resourceConnection->getTableName('inventory_source_item')
                ],
                $connection->quoteInto(
                    'source_item.source_code = ? and source_item.sku = isi.sku',
                    self::DEFAULT_STOCK_SOURCE
                ),
                []
            )->columns(
                [
                    'qty' => "isi.quantity",
                    'isSalable' => "isi.is_salable",
                    'productId' => 'isi.product_id',
                    'sku' => "isi.sku",
                    'stockId' => new Expression($stockId),
                    'manageStock' => 'stock_item.manage_stock',
                    'useConfigManageStock' => 'stock_item.use_config_manage_stock',
                    'backorders' => 'stock_item.backorders',
                    'useConfigBackorders' => 'stock_item.use_config_backorders',
                    'useConfigMinQty' => 'stock_item.use_config_min_qty',
                    'minQty' => 'stock_item.min_qty',
                ]);
    }

    /**
     * Get stocks
     *
     * @return array
     */
    private function getStocks(): array
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->fetchCol($connection->select()
            ->from(['stock' => $this->getTable('inventory_stock')], ['stock_id']));
    }
}
