<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider;

use Magento\Sales\Model\Order;

/**
 * Class for getting order state
 */
class State
{
    private const UNKNOWN_STATE = 0;

    private const MAPPING = [
        self::UNKNOWN_STATE => 0,
        Order::STATE_NEW => 1,
        Order::STATE_PENDING_PAYMENT => 2,
        Order::STATE_PROCESSING => 3,
        Order::STATE_COMPLETE => 4,
        Order::STATE_CLOSED => 5,
        Order::STATE_CANCELED => 6,
        Order::STATE_HOLDED => 7,
        Order::STATE_PAYMENT_REVIEW => 8,
        'RECEIVED' => 9, //TODO: unknown state
        'REJECTED' => 10, //TODO: unknown state
    ];

    /**
     * Getting state
     *
     * @param array $values
     * @return array
     */
    public function get(array $values): array
    {
        foreach ($values as &$value) {
            $value['state'] = self::MAPPING[$value['state']] ?? self::UNKNOWN_STATE;
        }

        return $values;
    }
}
