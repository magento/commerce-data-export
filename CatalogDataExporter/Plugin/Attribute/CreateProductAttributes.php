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

namespace Magento\CatalogDataExporter\Plugin\Attribute;

use Magento\CatalogDataExporter\Service\SystemAttributeRegistrar;
use Magento\DataExporter\Export\Processor;

/**
 * Dynamically register system attributes required to carry extra product data to SaaS
 * Overrides attribute metadata in the product attributes feed according to configuration
 *
 * Note: attributes registered dynamically to avoid race condition on upgrade when recently created attribute
 * (through patch) will be sent to SaaS before codebase is updated on the application node.
 */
class CreateProductAttributes
{
    private bool $attributesRegistered = false;
    public function __construct(
        private readonly SystemAttributeRegistrar $systemAttributeRegistrar
    ) {}

    /**
     * @param Processor $processor
     * @param array $feedItems
     * @param string $feedName
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(Processor $processor, array $feedItems, string $feedName): array
    {
        if ($feedName === 'productAttributes') {
            $this->modifyAttributeMetadata($feedItems);
        } elseif ($feedName === 'products') {
            $this->registerAttributes();
        }

        return $feedItems;
    }

    /**
     * Modifies the attribute metadata in the product attributes feed.
     *
     * @param array $productAttributes
     * @return void
     */
    private function modifyAttributeMetadata(array &$productAttributes): void
    {
        $attributeCodesForOverrides = $this->systemAttributeRegistrar->getAttributeCodes();
        foreach ($productAttributes as &$attribute) {
            $attributeCode = $attribute['attributeCode'] ?? null;
            if ($attributeCode  && in_array($attributeCode, $attributeCodesForOverrides, true)) {
                $overrides = $this->systemAttributeRegistrar->getExporterOverride($attributeCode);
                if ($overrides) {
                    $attribute = array_merge($attribute, $overrides);
                }
            }
        }
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function registerAttributes(): void
    {
        if ($this->attributesRegistered) {
            return;
        }
        $this->systemAttributeRegistrar->execute();
        $this->attributesRegistered = true;
    }
}
