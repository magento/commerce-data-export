<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Export;

use Magento\DataExporter\Config\ConfigInterface;

/**
 * Class Exporter
 *
 * Extracts and exports product data.
 */
class Exporter
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Exporter constructor.
     *
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * Extract records for record name
     *
     * @param string $recordName
     * @return array
     * @throws \Exception
     */
    public function extractRecords(string $recordName) : ?array
    {
        $requestedField = null;
        $type = $this->config->get('Export');
        foreach ($type['field'] as $field) {
            if ($field['name'] == $recordName) {
                $requestedField = $field;
                break;
            }
        }
        if (!$requestedField) {
            throw new \InvalidArgumentException('Unknown record name.');
        }
        return $this->extractFieldData($requestedField, null);
    }

    /**
     * Resolve scalar value
     *
     * @param array $fieldDefinition
     * @param string|null $reference
     * @return mixed
     */
    private function resolveScalarValue(array $fieldDefinition, ?string $reference)
    {
        if ($fieldDefinition['provider']) {
            return $reference;
        }
        return $reference;
    }

    /**
     * Extract field data
     *
     * @param array $rootField
     * @param string|null $reference
     * @return array
     */
    private function extractFieldData(array $rootField, ?string $reference) : ?array
    {
        $outputValue = null;
        if ($this->config->isScalar($rootField['type'])) {
            $outputValue = $this->resolveScalarValue($rootField, $reference);
        } else {
            $type = $this->config->get($rootField['type']);
            foreach ($type['field'] as $field) {
                $reference = null;
                $outputValue[$field['name']] = $this->extractFieldData($field, $reference);
            }
        }
        return $outputValue;
    }
}
