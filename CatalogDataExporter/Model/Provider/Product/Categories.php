<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
