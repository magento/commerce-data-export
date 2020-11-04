<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\CustomizableOptions;

use Magento\CatalogDataExporter\Model\Provider\Product\CustomOptions;
use Magento\CatalogDataExporter\Model\Provider\Product\OptionProviderInterface;

/**
 * Product options data provider, used for GraphQL resolver processing.
 */
class SelectableOptions implements OptionProviderInterface
{
    /**
     * @var string[]
     */
    private $optionTypes;

    /**
     * @var CustomOptions
     */
    private $customOptions;

    /**
     * @param CustomOptions $getOptions
     * @param string[] $optionTypes
     */
    public function __construct(
        CustomOptions $getOptions,
        array $optionTypes
    ) {
        $this->optionTypes = $optionTypes;
        $this->customOptions = $getOptions;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array
    {
        $productIds = [];
        $productStoreViews = [];
        $storeViewCode = current($values)['storeViewCode'];
        foreach ($values as $value) {
            $productIds[] = $value['productId'];
            $productStoreViews[$value['productId']] = $value['storeViewCode'];
        }

        $filteredProductOptions = $this->customOptions->get($productIds, $this->optionTypes, $storeViewCode);
        $output = [];
        foreach ($filteredProductOptions as $productId => $productOptions) {
            foreach ($productOptions as $key => $option) {
                $key = $productId . $productStoreViews[$productId] . $option['option_id'];
                $output[$key] = [
                    'productId' => (string)$productId,
                    'storeViewCode' => $productStoreViews[$productId],
                    'options' => [
                        'id' => $option['option_id'],
                        'title' => $option['title'],
                        'is_required' => $option['is_require'],
                        'type' => 'custom_option',
                        'render_type' => $option['type'],
                        'sort_order' => $option['sort_order'],
                        'product_sku' => $option['product_sku'],
                    ],
                ];

                $output[$key]['options']['values'] = $this->processOptionValues($option);
            }
        }

        return $output;
    }

    /**
     * Process option values.
     *
     * @param array $option
     * @return array
     */
    private function processOptionValues(
        array $option
    ): array {
        $resultValues = [];
        $values = $option['values'] ?? [];
        if (empty($values)) {
            return $resultValues;
        }
        foreach ($values as $value) {
            $resultValues[] = [
                'id' => $value['option_type_id'],
                'sort_order' => $value['sort_order'],
                'value' => $value['title'],
                'sku' => $value['sku'],
                'price_type' => strtoupper($value['price_type'] ?? 'DYNAMIC'),
                'price' => [
                    'regularPrice' => $value['price'],
                    'finalPrice' => $value['price'],
                ]
            ];
        }
        return $resultValues;
    }
}
