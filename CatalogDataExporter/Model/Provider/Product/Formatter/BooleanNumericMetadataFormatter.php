<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

/**
 * Generic class for definition of boolean and numeric attributes
 */
class BooleanNumericMetadataFormatter implements FormatterInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $attributeCodes;

    /**
     * @var array
     */
    private $frontendInput;

    /**
     * @var array
     */
    private $backendTypes;

    /**
     * @var array
     */
    private $validations;

    /**
     * @param string $type
     * @param array $attributeCodes
     * @param array $frontendInput
     * @param array $backendTypes
     * @param array $validations
     */
    public function __construct(
        string $type,
        array $attributeCodes = [],
        array $frontendInput = [],
        array $backendTypes = [],
        array $validations = []
    ) {
        $this->type = $type;
        $this->attributeCodes = $attributeCodes;
        $this->frontendInput = $frontendInput;
        $this->backendTypes = $backendTypes;
        $this->validations = $validations;
    }

    /**
     * @inheritdoc
     */
    public function format(array $row): array
    {
        $row[$this->type] =
            (\in_array($row['frontendInput'], $this->frontendInput) &&
            \in_array($row['dataType'], $this->backendTypes)) ||
            \in_array($row['validation'], $this->validations) ||
            \in_array($row['attributeCode'], $this->attributeCodes);

        return $row;
    }
}
