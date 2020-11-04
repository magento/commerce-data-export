<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Is product buyable data provider
 */
class Buyable
{
    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(array $values) : array
    {
        $output = [];
        foreach ($values as $value) {
            $output[] = [
                'productId' => $value['productId'],
                'storeViewCode' => $value['storeViewCode'],
                'buyable' => ($value['status'] == 'Enabled')
            ];
        }
        return $output;
    }
}
