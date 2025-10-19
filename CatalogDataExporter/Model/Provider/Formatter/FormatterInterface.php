<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Formatter;

/**
 * Provider data formatter
 */
interface FormatterInterface
{
    /**
     * Format provider data row
     *
     * @param array $row
     *
     * @return array
     */
    public function format(array $row) : array;
}
