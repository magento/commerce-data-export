<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
                $field = [
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'provider' => isset($fieldData['provider']) ? $fieldData['provider'] : null,
                    'repeated' => (isset($fieldData['repeated']) && $fieldData['repeated'] == "true") ? true : false
                ];
                if ($fieldData['type'] == 'ID') {
                    $idField = $fieldData['name'];
                }
                if (isset($fieldData['provider'])) {
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
