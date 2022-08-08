<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider;

use Magento\Framework\Url\Encoder;
use Magento\Framework\UrlInterface;

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
                    = $refundItem['qty'] * $refundItem['base_price'] - $refundItem['discount_amount'];
                $output[$refundItem['entityId']]['linkCreditMemoId'] = $refundItem['linkCreditMemoId'];
            }
        }

        return $output;
    }
}
