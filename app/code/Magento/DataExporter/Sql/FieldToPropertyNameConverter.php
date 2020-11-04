<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Sql;

/**
 * Class FieldToPropertyNameConverter
 *
 * Converts field to property name.
 */
class FieldToPropertyNameConverter
{
    /**
     * Convert to camel case
     *
     * @param string $str
     * @return string
     */
    public function toCamelCase(string $str) : string
    {
        $i = ["-","_"];
        $str = preg_replace('/([a-z])([A-Z])/', "\\1 \\2", $str);
        $str = preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $str);
        $str = str_replace($i, ' ', $str);
        $str = str_replace(' ', '', ucwords(strtolower($str)));
        $str = strtolower(substr($str, 0, 1)) . substr($str, 1);
        return $str;
    }
}
