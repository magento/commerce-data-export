<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider\Items;

use Magento\Framework\Serialize\Serializer\Json;

class OrderItemAdjustment
{
    private const PRODUCT_TYPE_BUNDLE = 'bundle';
    private const PRODUCT_TYPE_CONFIGURABLE = 'configurable';

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(
        Json $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * Retrieves and processes order items.
     *
     * @param  array $values
     * @return array
     */
    public function get(array $values): array
    {
        $output = [];
        foreach ($values as $orderItems) {
            foreach ($orderItems as $item) {
                $output[$item['entityId']]['entityId'] = $item['entityId'];
                $output[$item['entityId']]['itemsShippedTogether'] = $this->processRow($item);
            }
        }
        return $output;
    }

    /**
     * Processes a single row of order item data.
     *
     * @param  array $row
     * @return bool
     */
    private function processRow(array $row) : bool
    {
        if (!isset($row['productType'], $row['productOptions'])) {
            return false;
        }

        $result = false;
        if ($row['productType'] === self::PRODUCT_TYPE_BUNDLE) {
            $productOptions = $this->serializer->unserialize($row['productOptions']);
            $result = isset($productOptions['shipment_type'])
                && ((int)$productOptions['shipment_type'] === 0);
        }
        if ($row['productType'] === self::PRODUCT_TYPE_CONFIGURABLE) {
            $result = true;
        }
        return $result;
    }
}
