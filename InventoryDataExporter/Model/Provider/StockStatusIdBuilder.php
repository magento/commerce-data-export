<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

/**
 * Build ID based on stockId & sku
 */
class StockStatusIdBuilder
{
    /**
     * @param array $row
     * @return string
     */
    public static function build(array $row): string
    {
        if (!isset($row['stockId'], $row['sku'])) {
            throw new \RuntimeException(
                sprintf(
                    "inventory_data_exporter_stock_status indexer error: cannot build unique id from %s",
                    \var_export($row, true)
                )
            );
        }
        return \hash('md5', $row['stockId'] . "\0" . $row['sku']);
    }
}
