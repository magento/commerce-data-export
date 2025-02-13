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

namespace Magento\DataExporter\Config;

use DOMDocument;
use Magento\Framework\Config\ConverterInterface;

/**
 * A converter of reports configuration.
 *
 * Converts configuration data stored in XML format into corresponding PHP array.
 */
class Converter implements ConverterInterface
{
    /**
     * @var ConverterInterface
     */
    private $baseConverter;

    /**
     * @param ConverterInterface $converter
     */
    public function __construct(ConverterInterface $converter)
    {
        $this->baseConverter = $converter;
    }

    /**
     * Converts XML document into corresponding array.
     *
     * @param DOMDocument $source
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function convert($source)
    {
        $configData = $this->baseConverter->convert($source);
        if (!isset($configData['config'][0]['record'])) {
            return [];
        }

        $records = [];
        foreach ($configData['config'][0]['record'] as $queryData) {
            $records[$queryData['name']] = [
                'name' => $queryData['name'],
                'skipNull' => isset($queryData['skipNull']) && $queryData['skipNull'] == "true"
            ];
            $idField = null;
            foreach ($queryData['field'] as $fieldData) {
                $disabled = isset($fieldData['disabled']) && $fieldData['disabled'] == 'true';
                if ($disabled) {
                    continue;
                }
                $field = [
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'provider' => isset($fieldData['provider']) && $fieldData['provider'] !== 'null' ? $fieldData['provider'] : null,
                    'repeated' => isset($fieldData['repeated']) && $fieldData['repeated'] == 'true'
                ];
                if ($fieldData['type'] == 'ID') {
                    $idField = $fieldData['name'];
                }
                if (isset($field['provider'])) {
                    if (isset($fieldData['using'])) {
                        foreach ($fieldData['using'] as $usingField) {
                            $field['using'][$usingField['field']] = $usingField;
                        }
                    } else {
                        $field['using'][$idField] = ['field' => $idField];
                    }
                }
                $records[$queryData['name']]['field'][$field['name']] = $field;
            }
            $records[$queryData['name']]['ID'] = $idField;
        }
        return $records;
    }
}
