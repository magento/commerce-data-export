<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\QueryXml\Model\QueryProcessor;

/**
 * Get qty for sale. Calculated as "<qty from index> + SUM(<reservations>)"
 */
class QtyForSale
{
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    /**
     * @var string
     */
    private $queryName;

    /**
     * @var string[]
     */
    private $data;

    /**
     * @param QueryProcessor $queryProcessor
     * @param string $queryName
     * @param string[] $data
     */
    public function __construct(
        QueryProcessor $queryProcessor,
        string $queryName = 'inventoryExporterGetReservations',
        array $data = []
    ) {
        $this->data = $data;
        $this->queryName = $queryName;
        $this->queryProcessor = $queryProcessor;
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
        $queryArguments = $this->data;
        $queryArguments['skus'] = [];
        $queryArguments['stock_ids'] = [];
        $skuPerStockQty = [];
        $output = [];

        foreach ($values as $value) {
            $sku = $value['sku'];
            $stockId = $value['stockId'];
            $queryArguments['skus'][] = $sku;
            $queryArguments['stock_ids'][] = $stockId;
            $uniqueKey = StockStatusIdBuilder::build($value);
            $skuPerStockQty[$uniqueKey] = $value['qty'];

            // set default value
            $output[$uniqueKey] = [
                'sku' => $sku,
                'stockId' => $stockId,
                'qtyForSale' => $value['qty']
            ];
        }
        $cursor = $this->queryProcessor->execute($this->queryName, $queryArguments);
        while ($row = $cursor->fetch()) {
            $uniqueKey = StockStatusIdBuilder::build($row);
            if (isset($skuPerStockQty[$uniqueKey])) { // default value
                // TODO: if infinitive set to "0", check for "<0"
                $output[$uniqueKey] = [
                    'sku' => $row['sku'],
                    'stockId' => $row['stockId'],
                    'qtyForSale' => $skuPerStockQty[$uniqueKey] + $row['quantity']
                ];
            }
        }
        return $output;
    }
}
