<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
