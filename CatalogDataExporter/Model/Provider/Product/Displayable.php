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

namespace Magento\CatalogDataExporter\Model\Provider\Product;

/**
 * Is product displayable data provider
 */
class Displayable
{
    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array
    {
        $output = [];

        foreach ($values as $value) {
            $output[] = [
                'productId' => $value['productId'],
                'storeViewCode' => $value['storeViewCode'],
                'displayable' => (
                    $value['status'] === 'Enabled'
                    && in_array($value['visibility'], ['Catalog', 'Search', 'Catalog, Search'])
                )
            ];
        }

        return $output;
    }
}
