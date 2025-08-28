<?php
/**
 * Copyright 2025 Adobe
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

namespace AdobeCommerce\ExtraProductAttributes\Plugin;

use AdobeCommerce\ExtraProductAttributes\Provider\AdvancedInventoryProvider;
use AdobeCommerce\ExtraProductAttributes\Provider\AttributeSetProvider;
use Magento\DataExporter\Export\Processor;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Enrich product feed with additional attributes
 */
class AddAttributesToProductFeed
{
    private const ATTRIBUTE_CODE_ATTRIBUTE_SET = 'ac_attribute_set';

    private const ATTRIBUTE_CODE_INVENTORY = 'ac_inventory';

    private const ATTRIBUTE_CODE_TAX_CLASS = 'ac_tax_class';

    private const ADMIN_WEBSITE = 'admin';

    /**
     * @param AttributeSetProvider $attributeSetProvider
     */
    public function __construct(
        private readonly AttributeSetProvider $attributeSetProvider,
        private readonly SerializerInterface       $serializer,
        private readonly AdvancedInventoryProvider $advancedInventoryProvider
    ) {}

    /**
     * After process plugin for the Processor class.
     *
     * @param Processor $processor
     * @param array $feedItems
     * @param string $feedName
     * @return array
     * @throws \Throwable
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(Processor $processor, array $feedItems, string $feedName): array
    {
        if ($feedName === 'products') {
            $productIds = array_column($feedItems, 'productId');
            $attributeSetData = $this->attributeSetProvider->execute($productIds);
            $inventory = $this->advancedInventoryProvider->execute($productIds);
            foreach ($feedItems as &$product) {
                $this->addAttributeSetAttributeToProductFeed($product, $attributeSetData);
                $this->addTaxClassAttributeToProductFeed($product);
                $this->addInventoryAttributeToProductFeed($product, $inventory);
            }
        }
        return $feedItems;
    }

    /**
     * Adds the attribute to the product feed items.
     *
     * @param array $product
     * @param $attributeSetData
     * @return void
     */
    private function addAttributeSetAttributeToProductFeed(array &$product, $attributeSetData): void
    {
        $attributeSet = $attributeSetData[$product['productId']]['name'] ?? null;
        $product['attributes'][] = [
            'attributeCode' => self::ATTRIBUTE_CODE_ATTRIBUTE_SET,
            'value' => [$attributeSet],
        ];
    }

    /**
     * Adds the attribute to the product feed items.
     *
     * @param array $product
     * @return void
     */
    private function addTaxClassAttributeToProductFeed(array &$product): void
    {
        $taxClass = $product['taxClassId'] ?? null;
        $product['attributes'][] = [
            'attributeCode' => self::ATTRIBUTE_CODE_TAX_CLASS,
            'value' => [$taxClass],
        ];
    }

    /**
     * Adds the attribute to the product feed items.
     *
     * @param array $product
     * @param array $inventory
     * @return void
     */
    private function addInventoryAttributeToProductFeed(array &$product, array $inventory): void
    {
        $attributeData = $this->getInventoryDataForProduct(
            $inventory,
            (int)$product['productId'],
            $product['websiteCode']
        );
        if (!$attributeData) {
            return;
        }
        $product['attributes'][] = [
            'attributeCode' => self::ATTRIBUTE_CODE_INVENTORY,
            'value' => [$attributeData],
        ];
    }

    /**
     * @param array $inventory
     * @param int $productId
     * @param $websiteCode
     * @return string|null
     */
    private function getInventoryDataForProduct(array $inventory, int $productId, $websiteCode): ?string
    {
        $data = $inventory[$productId][$websiteCode]
            ?? $inventory[$productId][self::ADMIN_WEBSITE]
            ?? null;

        return $data ? $this->serializer->serialize($data) : null;
    }
}
