<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\DataExporter\Export\Processor;

/**
 * Fulfill "categories" field for Products feed.
 * Get categories from "categoryData" field which already contains needed data.
 */
class Categories
{
    /**
     * @param Processor $processor
     * @param array $result
     * @param string $fieldName
     * @return array
     */
    public function afterProcess(Processor $processor, array $result, string $fieldName): array
    {
        if ($fieldName !== 'products') {
            return $result;
        }
        foreach ($result as &$item) {
            if (isset($item['categoryData'])) {
                $item['categories'] = \array_column($item['categoryData'], 'categoryPath');
            }
        }
        return $result;
    }
}
