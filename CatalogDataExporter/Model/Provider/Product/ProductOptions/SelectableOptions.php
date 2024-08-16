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

namespace Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions;

use Magento\CatalogDataExporter\Model\Provider\Product\CustomOptions as CustomOptionsRepository;

/**
 * Product options data provider, used for GraphQL resolver processing.
 */
class SelectableOptions implements ProductOptionProviderInterface
{
    private const SELECTABLE_OPTION_TYPE = 'custom_option';

    /**
     * Selectable types
     *
     * @var string[]
     */
    private array $optionTypes = [
        'drop_down',
        'radio',
        'checkbox',
        'multiple',
    ];

    /**
     * @var CustomOptionsRepository
     */
    private $customOptionsRepository;

    /**
     * @var CustomizableSelectedOptionValueUid
     */
    private $optionValueUid;

    /**
     * SelectableOptions constructor.
     *
     * @param CustomOptionsRepository $customOptionsRepository
     * @param CustomizableSelectedOptionValueUid $optionValueUid
     */
    public function __construct(
        CustomOptionsRepository $customOptionsRepository,
        CustomizableSelectedOptionValueUid $optionValueUid
    ) {
        $this->customOptionsRepository = $customOptionsRepository;
        $this->optionValueUid = $optionValueUid;
    }

    /**
     * @inheritdoc
     *
     * @param array $values
     *
     * @return array
     */
    public function get(array $values) : array
    {
        $productIds = [];
        $storeViewCodes = [];
        foreach ($values as $value) {
            if (!\in_array($value['storeViewCode'], $storeViewCodes, true)) {
                $storeViewCodes[] = $value['storeViewCode'];
            }
            $productIds[] = $value['productId'];
        }
        $output = [];

        $defaultFilteredProductOptions = $this->customOptionsRepository->get($productIds, $this->optionTypes);
        $storeOptions = [];
        foreach ($storeViewCodes as $storeViewCode) {
            $filteredProductOptions = $this->customOptionsRepository->get(
                $productIds,
                $this->optionTypes,
                $storeViewCode,
                false
            );
            $storeViewOptionValues = $this->customOptionsRepository->getValuesByProductIds(
                $productIds,
                $this->optionTypes,
                $storeViewCode
            );
            foreach ($filteredProductOptions as $productId => $productOptions) {
                foreach ($productOptions as $option) {
                    $key = $productId . $storeViewCode . $option['option_id'];
                    $storeOptions[$key] = [
                        'product_id' => $productId,
                        'store_view_code' => $storeViewCode,
                        'option' => $this->replaceDefaultOptionData(
                            $defaultFilteredProductOptions[$productId][$option['option_id']],
                            $option ?? []
                        )
                    ];
                }
            }
            foreach ($defaultFilteredProductOptions as $productId => $defaultOptions) {
                foreach ($defaultOptions as $defaultOption) {
                    $key = $productId . $storeViewCode . $defaultOption['option_id'];
                    $storeOption = $storeOptions[$key]['option'] ?? $defaultOption;
                    $storeOption['values'] = $this->replaceDefaultOptionData(
                        $defaultOption['values'],
                        $storeViewOptionValues[$key] ?? []
                    );

                    $output[$key] = $this->formatOption((string)$productId, $storeViewCode, $storeOption);
                }
            }
        }

        return $output;
    }

    /**
     * Process option values.
     *
     * @param array $option
     *
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
                'id' => $this->optionValueUid->resolve([
                    CustomizableSelectedOptionValueUid::OPTION_ID => $value['option_id'],
                    CustomizableSelectedOptionValueUid::OPTION_VALUE_ID => $value['option_type_id']
                ]),
                'label' => $value['title'],
                'sortOrder' => $value['sort_order'],
                'isDefault' => $value['default'] ?? false,
                'imageUrl' => null,
                'qtyMutability' => null,
                'qty' => null,
                'infoUrl' => null,
                'sku' => $value['sku'],
                'price' => $value['price'],
                'priceType' => $value['price_type'],
            ];
        }
        return $resultValues;
    }

    /**
     * @param string $productId
     * @param string $storeViewCode
     * @param array $option
     * @return array
     */
    private function formatOption(string $productId, string $storeViewCode, array $option): array
    {
        return [
            'productId' => $productId,
            'storeViewCode' => $storeViewCode,
            'optionsV2' => [
                'id' => $option['option_id'],
                'label' => $option['title'],
                'sortOrder' => $option['sort_order'],
                'required' => $option['is_require'],
                'renderType' => $option['type'],
                'type' => self::SELECTABLE_OPTION_TYPE,
                'values' => $this->processOptionValues($option)
            ],
        ];
    }

    /**
     * @param array $defaultOption
     * @param array $storeOption
     * @return array
     */
    private function replaceDefaultOptionData(array $defaultOption, array $storeOption): array
    {
        foreach ($storeOption as $key => $value) {
            if (\is_array($value) && isset($defaultOption[$key]) && \is_array($defaultOption[$key])) {
                $defaultOption[$key] = $this->replaceDefaultOptionData($defaultOption[$key], $value);
            } elseif (null !== $value) {
                $defaultOption[$key] = $value;
            }
        }
        return $defaultOption;
    }
}
