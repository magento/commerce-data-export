<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product\CustomizableOptions;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface as CustomOption;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogDataExporter\Model\Provider\Product\CustomOptions;
use Magento\CatalogDataExporter\Model\Provider\Product\ProductShopperInputOptionProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Product shopper input options data provider.
 */
class ProductShopperInputOptions implements ProductShopperInputOptionProviderInterface
{
    /**
     * Option type name
     */
    private const OPTION_TYPE = 'custom-option';

    /**
     * @var string[]
     */
    private $productShopperInputOptionsTypes;

    /**
     * @var CustomOptions
     */
    private $customOptions;

    /**
     * @param CustomOptions $getOptions
     * @param array $optionTypes
     */
    public function __construct(
        CustomOptions $getOptions,
        array $optionTypes
    ) {
        $this->productShopperInputOptionsTypes = $optionTypes;
        $this->customOptions = $getOptions;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(array $values): array
    {
        $productIds = [];
        $output = [];
        $storeViewCode = current($values)['storeViewCode'];
        foreach ($values as $value) {
            $productIds[] = $value['productId'];
        }
        $filteredProductOptions = $this->customOptions->get(
            $productIds,
            $this->productShopperInputOptionsTypes,
            $storeViewCode
        );
        /** @var ProductInterface $product */
        foreach ($filteredProductOptions as $productId => $productOptions) {
            /** @var CustomOption $option */
            foreach ($productOptions as $key => $option) {
                $key = $productId . $storeViewCode . $option['option_id'];
                $output[$key] = [
                    'productId' => (string)$productId,
                    'storeViewCode' => $storeViewCode,
                    'productShopperInputOptions' => [
                        'id' => $this->buildUid($option['option_id']),
                        'label' => $option['title'],
                        'required' => $option['is_require'],
                        'product_sku' => $option['product_sku'],
                        'sort_order' => $option['sort_order'],
                        'render_type' => $option['type'],
                        'price' => $option['price'],
                        'file_extension' => $option['file_extension'],
                        'range' => [
                            'to' => $option['max_characters']
                        ],
                        'image_size_x' => $option['image_size_x'],
                        'image_size_y' => $option['image_size_y']
                    ],
                ];
            }
        }

        return $output;
    }

    /**
     * Build the UID for the shopper input option
     *
     * @param string $optionId
     * @return string
     */
    private function buildUid(string $optionId): string
    {
        return base64_encode(implode('/', [self::OPTION_TYPE, $optionId]));
    }
}
