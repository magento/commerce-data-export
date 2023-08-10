<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
    private $optionTypes = [
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
        $storeViewCode = current($values)['storeViewCode'];
        foreach ($values as $value) {
            $productIds[] = $value['productId'];
        }

        $filteredProductOptions = $this->customOptionsRepository->get($productIds, $this->optionTypes, $storeViewCode);
        $output = [];
        foreach ($filteredProductOptions as $productId => $productOptions) {
            foreach ($productOptions as $key => $option) {
                $key = $productId . $storeViewCode . $option['option_id'];
                $output[$key] = [
                    'productId' => (string)$productId,
                    'storeViewCode' => $storeViewCode,
                    'optionsV2' => [
                        'id' => $option['option_id'],
                        'label' => $option['title'],
                        'sortOrder' => $option['sort_order'],
                        'required' => $option['is_require'],
                        'renderType' => $option['type'],
                        'type' => self::SELECTABLE_OPTION_TYPE
                    ],
                ];

                $output[$key]['optionsV2']['values'] = $this->processOptionValues($option);
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
}
