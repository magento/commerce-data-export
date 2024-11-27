<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Indexer;

use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\CustomizableEnteredOptionValueUid;
use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\CustomizableSelectedOptionValueUid;
use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\DownloadableLinksOptionUid;

/**
 * Class responsible for indexing product price build event data
 */
class PriceBuilder
{
    /**
     * @var CustomizableEnteredOptionValueUid
     */
    private $optionValueUid;

    /**
     * @var DownloadableLinksOptionUid
     */
    private $downloadableLinksOptionUid;

    /**
     * @param CustomizableEnteredOptionValueUid $optionValueUid
     * @param DownloadableLinksOptionUid $downloadableLinksOptionUid
     */
    public function __construct(
        CustomizableEnteredOptionValueUid $optionValueUid,
        DownloadableLinksOptionUid $downloadableLinksOptionUid
    ) {
        $this->optionValueUid = $optionValueUid;
        $this->downloadableLinksOptionUid = $downloadableLinksOptionUid;
    }

    /**
     * Build event data for product custom option price
     *
     * @param array $data
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function buildCustomOptionPriceEventData(array $data): array
    {
        $id = $this->optionValueUid->resolve([CustomizableEnteredOptionValueUid::OPTION_ID => $data['option_id']]);

        return [
            'id' => $id,
            'value' => $data['price'],
            'price_type' => $data['price_type'],
        ];
    }

    /**
     * Build complex product event data.
     *
     * @param string $parentId
     * @param string $variationId
     * @param string $linkType
     *
     * @return array
     */
    public function buildComplexProductEventData(string $parentId, string $variationId, string $linkType): array
    {
        return [
            'id' => $parentId,
            'variation_id' => $variationId,
            'price_type' => $linkType,
        ];
    }

    /**
     * Build custom option type price event data
     *
     * @param array $data
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function buildCustomOptionTypePriceEventData(array $data): array
    {
        $id = $this->optionValueUid->resolve(
            [
                CustomizableSelectedOptionValueUid::OPTION_ID => $data['option_id'],
                CustomizableSelectedOptionValueUid::OPTION_VALUE_ID => $data['option_type_id'],
            ]
        );

        return [
            'id' => $id,
            'value' => $data['price'],
            'price_type' => $data['price_type'],
        ];
    }

    /**
     * Build downloadable link price event data.
     *
     * @param string $entityId
     * @param string $value
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function buildDownloadableLinkPriceEventData(string $entityId, string $value): array
    {
        $id = $this->downloadableLinksOptionUid->resolve([DownloadableLinksOptionUid::OPTION_ID => $entityId]);
        return [
            'id' => $id,
            'value' => $value,
        ];
    }

    /**
     * Build product price event data.
     *
     * @param string $entityId
     * @param string $attributeCode
     * @param string|null $attributeValue
     *
     * @return array
     */
    public function buildProductPriceEventData(string $entityId, string $attributeCode, ?string $attributeValue): array
    {
        return [
            'id' => $entityId,
            'attribute_code' => $attributeCode,
            'value' => $attributeValue,
        ];
    }

    /**
     * Build tier price event data.
     *
     * @param string $entityId
     * @param string $qty
     * @param string|null $priceType
     * @param string|null $value
     * @return array
     */
    public function buildTierPriceEventData(string $entityId, string $qty, ?string $priceType, ?string $value): array
    {
        return [
            'id' => $entityId,
            'attribute_code' => 'tier_price',
            'qty' => $qty,
            'price_type' => $priceType,
            'value' => $value,
        ];
    }
}
