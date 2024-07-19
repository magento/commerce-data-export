<?php
/**
 * Copyright 2021 Adobe
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
