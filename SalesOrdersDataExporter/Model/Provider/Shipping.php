<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\SalesOrdersDataExporter\Model\Provider;

/**
 * Class for getting shipping information
 */
class Shipping
{
    /**
     * @param array $values
     * @return array
     */
    public function get(array $values): array
    {
        $output = [];
        foreach ($values as $row) {
            $output[] = [
                'shipping' => $row,
                'commerceOrderId' => $row['commerceOrderId'],
            ];
        }
        return $output;
    }
}
