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

/**
 * Class for getting credit memo base row total
 */
class CreditMemoBaseRowTotal
{
    /**
     * Getting base row total.
     *
     * @param array $values
     * @return array
     */
    public function get(array $values): array
    {
        //price for row items (qty*base_price * [coefficient]) May include adjusted price including promotions
        $output = [];
        foreach ($values as $group) {
            foreach ($group as $refundItem) {
                $output[$refundItem['entityId']]['baseRowTotal']
                    = $refundItem['qtyRefunded'] * $refundItem['basePrice'] - $refundItem['baseDiscountAmount'];
                $output[$refundItem['entityId']]['entityId'] = $refundItem['entityId'];
            }
        }

        return $output;
    }
}
