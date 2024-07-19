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

namespace Magento\DataExporter\Model\Provider;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Export\Request\Node;

/**
 * Convert date to standard RFC 3339
 */
class DateConverter
{
    /**
     * Get data from query
     *
     * @param array $values
     * @param Node $node
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values, Node $node): array
    {
        $fieldName = $node->getField()['name'];
        $fieldParentLink = array_key_first($node->getField()['using']);
        $values = $this->flatten($values);
        $output = [];
        foreach ($values as $value) {
            if (empty($value[$fieldName])) {
                continue;
            }
            try {
                $output[] = [
                    $fieldName => (new \DateTime($value[$fieldName]))->format(\DateTimeInterface::RFC3339),
                    $fieldParentLink => $value[$fieldParentLink]
                ];
            } catch (\Exception $e) {
                throw new UnableRetrieveData(
                    \sprintf(
                        "Cannot convert date for field $fieldName, origin value: %s, item:%s, error:%s",
                        $value[$fieldName],
                        var_export($value, true),
                        $e->getMessage()
                    ),
                    0,
                    $e
                );
            }
        }
        return $output;
    }

    private function flatten($values)
    {
        if (isset(current($values)[0])) {
            return array_merge([], ...array_values($values));
        }
        return $values;
    }
}
