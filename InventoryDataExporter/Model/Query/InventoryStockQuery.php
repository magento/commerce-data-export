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

    /**
     * @param ResourceConnection $resourceConnection
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
     * @param int $stockId
     * @return Select
     */
    public function getQuery(array $skus): Select
    {
        $connection = $this->resourceConnection->getConnection();
        foreach ($this->getStocks() as $stockId) {
            $stockId = (int)$stockId;
            $select = $connection->select()
                ->from(['isi' => $this->getTable(sprintf('inventory_stock_%s', $stockId))], [])
                ->where('isi.sku IN (?)', $skus);

            if ($this->defaultStockProvider->getId() === $stockId) {
                // inventory_stock_1 is view used as a fallback
                $select->joinInner(
                    [
                        'stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item'),
                    ],
                    'stock_item.product_id = isi.product_id',
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
                    ]);
            } else {
                $select->columns(
                    [
                        'qty' => "isi.quantity",
                        'isSalable' => "isi.is_salable",
                        'sku' => "isi.sku",
                        'stockId' => new Expression($stockId),
                        'manageStock' => new Expression(0),
                        'useConfigManageStock' => new Expression(0),
                        'backorders' => new Expression(0),
                        'useConfigBackorders' => new Expression(0),
                    ]
                );

            }
            $selects[] = $select;
        }
        return $connection->select()->union($selects, Select::SQL_UNION_ALL);

    }

    /**
     * Get stocks
     *
     * @return array
     */
    private function getStocks(): array
    {
        // TODO: add batching
        $connection = $this->resourceConnection->getConnection();
        return $connection->fetchCol($connection->select()
            ->from(['stock' => $this->getTable('inventory_stock')], ['stock_id']));
    }
}
