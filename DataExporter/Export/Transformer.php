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

namespace Magento\DataExporter\Export;

use Magento\DataExporter\Config\ConfigInterface;
use Magento\DataExporter\Export\Request\Info;

/**
 * Class Transformer
 *
 * Transforms info object into data.
 */
class Transformer
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * Check if type is scalar
     *
     * @param string $typeName
     * @return bool
     */
    private function isScalar(string $typeName) : bool
    {
        return $this->config->isScalar($typeName);
    }

    /**
     * Transform info data
     *
     * @param Info $info
     * @param array $snapshot
     * @return array
     */
    public function transform(Info $info, array $snapshot) : array
    {
        $result = [];
        $key = $this->getKey($info->getRootNode()->getField());
        if (!isset($snapshot[$key])) {
            return $result;
        }
        $data = $this->convertComplexData(
            $info->getRootNode()->getField(),
            $snapshot,
            null
        );
        return $data ? $data : [];
    }

    /**
     * Get field key
     *
     * @param array $field
     * @return string
     */
    private function getKey(array $field) : string
    {
        return base64_encode(json_encode($field));
    }

    /**
     * Cast scalar value
     *
     * @param string $type
     * @param string|null $value
     * @return bool|float|int|string|null
     */
    private function castScalarValue(string $type, $value)
    {
        $result = null;
        switch ($type) {
            case "ID":
            case "String":
                $result = (string) $value;
                break;
            case "Int":
                $result = (int) $value;
                break;
            case "Float":
                $result = (float) $value;
                break;
            case "Boolean":
                $result = (bool) $value;
                break;
        }
        return $result;
    }

    /**
     * Cast field to type
     *
     * @param array $rootField
     * @param mixed $value
     * @return bool|float|int|string|null
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @phpcs:disable Magento2.Annotation.MethodArguments
     * @phpcs:disable Generic.Metrics.NestingLevel
     * @phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall
     */
    private function castToFieldType(array $rootField, $value)
    {
        $result = null;
        if ($this->isScalar($rootField['type'])) {
            if ($rootField['repeated']) {
                if (is_array($value)) {
                    for ($i = 0, $count=count($value); $count > $i; $i++) {
                        $result[$i] = $this->castScalarValue($rootField['type'], $value[$i]);
                    }
                }
            } else {
                $result = $this->castScalarValue($rootField['type'], $value);
            }
        } else {
            $type = $this->config->get($rootField['type']);
            if ($rootField['repeated']) {
                if (is_array($value)) {
                    for ($i=0, $count=count($value); $count > $i; $i++) {
                        foreach ($type['field'] as $field) {
                            if (isset($value[$i][$field['name']])) {
                                $result[$i][$field['name']] =
                                    $this->castToFieldType($field, $value[$i][$field['name']]);
                            } elseif (!$type['skipNull']) {
                                $result[$i][$field['name']] = null;
                            }
                        }
                    }
                }
            } else {
                foreach ($type['field'] as $field) {
                    if (isset($value[$field['name']])) {
                        $result[$field['name']] = $this->castToFieldType($field, $value[$field['name']]);
                    } elseif (!$type['skipNull']) {
                        $result[$field['name']] = null;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Convert complex row
     *
     * @param array $row
     * @param array $type
     * @param array $snapshot
     * @return array
     */
    private function convertComplexRow(array $row, array $type, array $snapshot) : array
    {
        $result = [];
        foreach ($type['field'] as $field) {
            if ($field['provider'] != null) {
                $key = $this->getKey($field);
                if (isset($snapshot[$key])) {
                    $lookupReference = LookupBuilder::build($field, $row);
                    //todo: add Filter cond
                    $result[$field['name']] = $this->convertComplexData($field, $snapshot, $lookupReference);
                }
            } elseif (isset($row[$field['name']])) {
                $result[$field['name']] = $this->castToFieldType($field, $row[$field['name']]);
            }
        }
        return $result;
    }

    /**
     * Convert complex data
     *
     * @param array $field
     * @param array $snapshot
     * @param string $lookup
     * @return array
     *
     * @phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall
     */
    private function convertComplexData(array $field, array $snapshot, ?string $lookup)
    {
        //Add check if data already present than no need to convert it
        if ($lookup) {
            if (!isset($snapshot[$this->getKey($field)][$lookup])) {
                return null;
            }
            $data = $snapshot[$this->getKey($field)][$lookup];
        } else {
            $data = $snapshot[$this->getKey($field)];
        }
        $result = null;
        if ($this->isScalar($field['type'])) {
            $result = $this->castToFieldType($field, $data);
        } else {
            $type = $this->config->get($field['type']);
            if ($field['repeated']) {
                for ($i=0, $count=count($data); $i < $count; $i++) {
                    $result[$i] = $this->convertComplexRow($data[$i], $type, $snapshot);
                }
            } else {
                $result = $this->convertComplexRow($data, $type, $snapshot);
            }
        }
        return $result;
    }
}
