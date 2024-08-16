<?php
/**
 * Copyright 2021 Adobe
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
        $storeViewCodes = [];
        foreach ($values as $value) {
            if (!\in_array($value['storeViewCode'], $storeViewCodes, true)) {
                $storeViewCodes[] = $value['storeViewCode'];
            }
            $productIds[] = $value['productId'];
        }
        $defaultFilteredProductOptions = $this->customOptions->get($productIds, $this->productShopperInputOptionsTypes);
        foreach ($storeViewCodes as $storeViewCode) {
            $filteredProductOptions = $this->customOptions->get(
                $productIds,
                $this->productShopperInputOptionsTypes,
                $storeViewCode
            );
            /** @var ProductInterface $product */
            foreach ($defaultFilteredProductOptions as $productId => $productOptions) {
                foreach ($productOptions as $defaultOption) {
                    $storeViewOption = $filteredProductOptions[$productId][$defaultOption['option_id']] ?? [];
                    $key = $productId . $storeViewCode . $defaultOption['option_id'];
                    $output[$key] = [
                        'productId' => (string)$productId,
                        'storeViewCode' => $storeViewCode,
                        'shopperInputOptions' => [
                            'id' => $this->buildUid($defaultOption['option_id']),
                            'label' => $storeViewOption['title'] ?? $defaultOption['title'],
                            'required' => $storeViewOption['is_require'] ?? $defaultOption['is_require'],
                            'productSku' => $storeViewOption['product_sku'] ?? $defaultOption['product_sku'],
                            'sortOrder' => $storeViewOption['sort_order'] ?? $defaultOption['sort_order'],
                            'renderType' => $storeViewOption['type'] ?? $defaultOption['type'],
                            'price' => $storeViewOption['price'] ?? $defaultOption['price'],
                            'sku' => $storeViewOption['sku'] ?? $defaultOption['sku'],
                            'fileExtension' => $storeViewOption['file_extension'] ?? $defaultOption['file_extension'],
                            'range' => [
                                'to' => $storeViewOption['max_characters'] ?? $defaultOption['max_characters']
                            ],
                            'imageSizeX' => $storeViewOption['image_size_x'] ?? $defaultOption['image_size_x'],
                            'imageSizeY' => $storeViewOption['image_size_y'] ?? $defaultOption['image_size_y']
                        ],
                    ];
                }
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
