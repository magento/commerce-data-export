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
            $skuPerStockQty[StockStatusIdBuilder::build($value)] = $value['qty'];
        }
        $cursor = $this->queryProcessor->execute($this->queryName, $queryArguments);
        while ($row = $cursor->fetch()) {
            $uniqueKey = StockStatusIdBuilder::build($row);
            if (isset($skuPerStockQty[$uniqueKey])) {
                // TODO: if infinitive set to "0", check for "<0"
                $output[] = [
                    'sku' => $row['sku'],
                    'stockId' => $row['stockId'],
                    'qtyForSale' => $skuPerStockQty[$uniqueKey] + $row['quantity']
                ];
            }
        }
        return $output;
    }
}
