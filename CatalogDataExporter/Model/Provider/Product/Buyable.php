<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
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
            $output[$this->getKey($value)] = [
                'productId' => $value['productId'],
                'storeViewCode' => $value['storeViewCode'],
                'buyable' => ($value['status'] == 'Enabled')
            ];
        }
        return $output;
    }

    /**
     * @param array $stockItem
     * @return string
     */
    private function getKey(array $productItem): string
    {
        return $productItem['storeViewCode'] . '_' . $productItem['productId'];
    }
}
