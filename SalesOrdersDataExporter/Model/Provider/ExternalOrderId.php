<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider;

use Magento\DataExporter\Export\Request\Node;

/**
 * Set default value of External Id for Magento Sales Channel
 * External Sales Channel have to override value if needed
 */
class ExternalOrderId
{
    private const DEFAULT_SALES_CHANNEL = 'magento';

    /**
     * @param array $values
     * @param Node $node
     * @return array
     */
    public function get(array $values, Node $node): array
    {
        $fieldParentLink = array_key_first($node->getField()['using']);
        $fieldName = $node->getField()['name'];

        $output = [];
        foreach ($values as $value) {
            $uniqueKey = $value[$fieldParentLink];
            $output[$uniqueKey] = [
                $fieldName => [
                    'id' => $value['commerceOrderId'],
                    'salesChannel' => self::DEFAULT_SALES_CHANNEL,
                ],
                $fieldParentLink => $uniqueKey
            ];
        }
        return $output;
    }
}
