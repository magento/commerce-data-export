<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider;

class MultiLineStreet
{

    /**
     * Splits the 'street' field (only when present) using newline char as separator.
     *
     * @param  array $values containing 'street' field
     * @return array containing 'multi_line_street' field
     */
    public function get(array $values): array
    {
        $output = [];
        foreach ($values as $value) {
            if (isset($value['street'])) {
                foreach ((explode("\n", $value['street'])) as $streetLine) {
                    $output[] = [
                        'commerceOrderId' => $value['commerceOrderId'],
                        'addressType' => $value['addressType'],
                        'multiLineStreet' => $streetLine
                    ];
                }
            }
        }

        return $output;
    }
}
