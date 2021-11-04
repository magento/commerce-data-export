<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\CatalogInventory\Api\StockConfigurationInterface;

/**
 * Class for getting infinite stock value for stock item.
 */
class InfiniteStock
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->stockConfiguration = $stockConfiguration;
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
        $configManageStock = $this->stockConfiguration->getManageStock();
        $configBackorders = $this->stockConfiguration->getBackorders();
        $output = [];
        foreach ($values as $row) {
            $itemInfiniteStock['sku'] = $row['sku'];
            $itemInfiniteStock['infiniteStock'] = $this->getIsInfiniteStock(
                $row,
                (bool)$configManageStock,
                (bool)$configBackorders
            );
            $output[] = $itemInfiniteStock;
        }
        return $output;
    }

    /**
     * Check is item stock is infinite
     *
     * @param array $row
     * @param bool $configManageStock
     * @param bool $configBackorders
     * @return bool
     */
    private function getIsInfiniteStock(array $row, bool $configManageStock, bool $configBackorders): bool
    {
        $isInfinite = false === $configManageStock || true === $configBackorders;
        if (false === (bool)$row['useConfigManageStock'] && isset($row['manageStock'])) {
            $isInfinite = !(bool)$row['manageStock'];
        }
        // With Backorders enabled, and Out-of-Stock Threshold = 0 allows for infinite backorders
        if (false === $isInfinite && false === (bool)$row['useConfigBackorders']
            && false === (bool)$row['useConfigMinQty'] && isset($row['backorders'], $row['minQty'])) {
            $isInfinite = (bool)$row['backorders'] && (float)$row['minQty'] === 0.0;
        }
        return $isInfinite;
    }
}
