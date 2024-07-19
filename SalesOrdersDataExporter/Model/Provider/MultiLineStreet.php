<?php
/**
 * Copyright 2023 Adobe
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
