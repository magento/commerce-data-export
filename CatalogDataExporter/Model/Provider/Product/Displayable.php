<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
