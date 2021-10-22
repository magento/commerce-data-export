<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\Framework\Stdlib\DateTime;

/**
 * Class for getting updated at value for stock item.
 */
class UpdatedAt
{
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param DateTime $dateTime
     */
    public function __construct(
        DateTime $dateTime
    ) {
        $this->dateTime = $dateTime;
    }

    /**
     * Getting inventory stock statuses.
     *
     * @param array $values
     * @return array
     */
    public function get(array $values): array
    {
        $output = [];
        foreach ($values as $value) {
            $output[] = [
                'sku' => $value['sku'],
                'stockId' => $value['stockId'],
                'updatedAt' => $this->dateTime->formatDate(time())
            ];
        }
        return $output;
    }
}
