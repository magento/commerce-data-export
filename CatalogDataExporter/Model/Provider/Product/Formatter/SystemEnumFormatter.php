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

namespace Magento\CatalogDataExporter\Model\Provider\Product\Formatter;

/**
 * Class SystemEnumFormatter
 *
 * Formats system enum values.
 */
class SystemEnumFormatter implements FormatterInterface
{
    /**
     * @var array
     */
    private $systemEnums;

    /**
     * @param array $systemEnums
     */
    public function __construct(
        array $systemEnums = []
    ) {
        $this->systemEnums = $systemEnums;
    }

    /**
     * Format data
     *
     * @param array $row
     * @return array
     */
    public function format(array $row): array
    {
        foreach ($this->systemEnums as $enumName => $enumMap) {
            if (isset($row[$enumName])) {
                $row[$enumName] = $enumMap[$row[$enumName]] ?? $enumMap['_'] ?? null;
            }
        }
        return $row;
    }
}
