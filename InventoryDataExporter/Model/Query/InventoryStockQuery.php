<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
     * @param array $skus
     * @param bool $defaultStock
     * @return Select|null
     */
    public function getQuery(array $skus): ?Select
    {
        $connection = $this->resourceConnection->getConnection();
        $selects = [];
        foreach ($this->getStocks() as $stockId) {
            $stockId = (int)$stockId;
            if ($this->defaultStockProvider->getId() === $stockId) {
                continue;
            }
            $select = $connection->select()
                ->from(['isi' => $this->getTable(sprintf('inventory_stock_%s', $stockId))], [])
                ->joinLeft(
                    [
                        'product' => $this->resourceConnection->getTableName('catalog_product_entity'),
                    ],
                    'product.sku = isi.sku',
                    []
                )->joinLeft(
                    [
                        'stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item'),
                    ],
                    'stock_item.product_id = product.entity_id',
                    []
                )->where('isi.sku IN (?)', $skus)
                ->columns(
                    [
                        'qty' => "isi.quantity",
                        'isSalable' => "isi.is_salable",
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
     * @param array $skus
     * @return Select
     * @see \Magento\InventoryCatalog\Setup\Patch\Schema\CreateLegacyStockStatusView
     */
    public function getQueryForDefaultStock(array $skus): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $stockId = $this->defaultStockProvider->getId();
        return $connection->select()
            ->from(['isi' => $this->getTable(sprintf('inventory_stock_%s', $stockId))], [])
            ->where('isi.sku IN (?)', $skus)
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
