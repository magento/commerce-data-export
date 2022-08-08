<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
