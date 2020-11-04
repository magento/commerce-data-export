<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\CustomizableOptions;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface as CustomOption;
use Magento\CatalogDataExporter\Model\Provider\Product\CustomOptions;
use Magento\CatalogDataExporter\Model\Provider\Product\OptionProviderInterface;

/**
 * Product entered options data provider, used for GraphQL resolver processing.
 */
class EnteredOptions implements OptionProviderInterface
{
    /**
     * @var string[]
     */
    private $enteredOptionTypes;

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
        $this->enteredOptionTypes = $optionTypes;
        $this->customOptions = $getOptions;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(array $values) : array
    {
        $productIds = [];
        $storeViewCode = current($values)['storeViewCode'];
        foreach ($values as $value) {
            $productIds[] = $value['productId'];
        }
        $filteredProductOptions = $this->customOptions->get($productIds, $this->enteredOptionTypes, $storeViewCode);

        $output = [];
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        foreach ($filteredProductOptions as $productId => $productOptions) {
            /** @var CustomOption $option */
            foreach ($productOptions as $key => $option) {
                $key = $productId . $storeViewCode . $option['option_id'];
                $output[$key] = [
                    'productId' => (string)$productId,
                    'storeViewCode' => $storeViewCode,
                    'entered_options' => [
                        'id' => $option['option_id'],
                        'value' => $option['title'],
                        'is_required' => $option['is_require'],
                        'type' => 'custom_option',
                        'render_type' => $option['type'],
                        'sort_order' => $option['sort_order'],
                        'product_sku' => $option['product_sku'],
                        'price' => [
                            'regularPrice' => $option['price'],
                            'finalPrice' => $option['price'],
                        ],
                        'price_type' => $option['price_type'] ?? 'dynamic',
                        'sku' => $option['sku'],
                        'max_characters' => $option['max_characters'],
                        'file_extension' => $option['file_extension'],
                        'image_size_x' => $option['image_size_x'],
                        'image_size_y' => $option['image_size_y'],
                    ],
                ];
            }
        }

        return $output;
    }
}
