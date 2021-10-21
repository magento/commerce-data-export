<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryDataExporter\Model\Query\InventoryStockQuery;

/**
 * Get inventory stock statuses
 * Fulfill fields for StockItemStatus record:
 *  [
 *    stockId,
 *    qty,
 *    isSalable,
 *    sku
 * ]
]
 */
class StockStatus
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var InventoryStockQuery
     */
    private $query;

    public function __construct(
        ResourceConnection $resourceConnection,
        InventoryStockQuery $query
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->query = $query;
    }

    /**
     * Getting inventory stock statuses.
     *
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values): array
    {
        $skus = \array_column($values, 'sku');
        $connection = $this->resourceConnection->getConnection();
        $output = [];
        //TODO: limit stock-source ids

        $select = $this->query->getQuery($skus);
        try {
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $row['id'] = StockStatusIdBuilder::build($row);
                // set default values
                $row['infiniteStock'] = false;
                $row['qtyForSale'] = $row['qty'];
                $output[] = $row;
            }
        } catch (\Throwable $e) {
            // handle case when view "inventory_stock_1" for default Stock does not exists
            $output += \array_map(static function ($sku) {
                $row = [
                    'qty' => 0,
                    'isSalable' => false,
                    'sku' => $sku,
                    'stockId' => 1,
                    'infiniteStock' => false,
                    'qtyForSale' => 0
                ];
                $row['id'] = StockStatusIdBuilder::build($row);
                return $row;
            }, $skus);
        }

        return $output;
    }
}
