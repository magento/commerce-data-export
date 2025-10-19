<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\DataExporter\Model\FailedItemsRegistry;

/**
 * Is product displayable data provider
 */
class Displayable
{
    private FailedItemsRegistry $failedRegistry;

    /**
     * @param FailedItemsRegistry $failedRegistry
     */
    public function __construct(FailedItemsRegistry $failedRegistry)
    {
        $this->failedRegistry = $failedRegistry;
    }

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
            try {
                $output[] = [
                    'productId' => $value['productId'],
                    'storeViewCode' => $value['storeViewCode'],
                    'displayable' => (
                        $value['status'] === 'Enabled'
                        && in_array($value['visibility'], ['Catalog', 'Search', 'Catalog, Search'])
                    )
                ];
            } catch (\Throwable $e) {
                $this->failedRegistry->addFailed($value, $e);
            }
        }

        return $output;
    }
}
