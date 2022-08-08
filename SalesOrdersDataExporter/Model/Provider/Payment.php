<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class for getting payment information
 */
class Payment
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var string[]
     */
    private $mapping;

    /**
     * @var string[]
     */
    private $unserializeKeys;

    /**
     * @param Json $serializer
     * @param string[] $unserializeKeys
     * @param string[] $mapping
     */
    public function __construct(
        Json $serializer,
        array $unserializeKeys = [],
        array $mapping = []
    ) {
        $this->serializer = $serializer;
        $this->mapping = $mapping;
        $this->unserializeKeys = $unserializeKeys;
    }

    /**
     * Getting sales order.
     *
     * @param array $values
     * @return array
     */
    public function get(array $values): array
    {

        $output = [];
        foreach ($values as $row) {
            $tmp = [];
            foreach ($this->unserializeKeys as $unserializeKey) {
                $tmp[$unserializeKey] = $this->serializer->unserialize($row[$unserializeKey]);
                unset($row[$unserializeKey]);
            }
            foreach ($this->mapping as $field => $index) {
                $value = $this->getNestedValue($tmp, $index);
                $row[$field] = $value ?? null;
            }
            $output[] = [
                'payment' => $row,
                'commerceOrderId' => $row['commerceOrderId'],
            ];
        }
        return $output;
    }

    /**
     * Get nested array value.
     *
     * @param array $array
     * @param string $path
     * @return mixed
     */
    private function getNestedValue(array $array, string $path)
    {
        $arrayPath = explode('.', $path);
        $reduce = static function ($source, $key) {
            return $source[$key] ?? null;
        };
        return array_reduce($arrayPath, $reduce, $array);
    }
}
