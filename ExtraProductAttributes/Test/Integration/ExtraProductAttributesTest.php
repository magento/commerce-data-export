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

namespace AdobeCommerce\ExtraProductAttributes\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for extra product attributes export
 */
class ExtraProductAttributesTest extends \Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper
{
    /**
     * Validate extra product attributes data
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testExtraProductAttributes(): void
    {
        $skus = ['simple1', 'simple2'];
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($skus as $sku) {
            foreach ($storeViewCodes as $storeViewCode) {
                $store = $this->storeManager->getStore($storeViewCode);
                $product = $this->productRepository->get($sku, false, $store->getId());
                $product->setTypeInstance(Bootstrap::getObjectManager()->create(Simple::class));

                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateExtraAttributesData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate extra product attributes in extracted product data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     */
    private function validateExtraAttributesData(ProductInterface $product, array $extractedProduct): void
    {
        $expectedAttributes = [];

        // Validate tax class attribute
        $expectedAttributes['ac_tax_class'] = [
            'attributeCode' => 'ac_tax_class',
            'value' => ['Taxable Goods']
        ];

        // Validate attribute set attribute
        $attributeSetId = $product->getAttributeSetId();
        $expectedAttributes['ac_attribute_set'] = [
            'attributeCode' => 'ac_attribute_set',
            'value' => ['SaaSCatalogAttributeSet']
        ];

        // Validate inventory attribute
        $inventoryData = $this->getInventoryData($product);
        $expectedAttributes['ac_inventory'] = [
            'attributeCode' => 'ac_inventory',
            'value' => [$inventoryData]
        ];

        $this->validateAttributeData($product, $extractedProduct, $expectedAttributes);
    }

    /**
     * Get inventory data for product
     *
     * @param ProductInterface $product
     * @return string|null
     */
    private function getInventoryData(ProductInterface $product): ?string
    {
        $stockItem = [
            // overriden settings
            'simple1' => [
                'manageStock' => true,
                'cartMinQty' => 3,
                'cartMaxQty' => 100,
                'backorders' => 'allow',
                'enableQtyIncrements' => false,
                'qtyIncrements' => 2
            ],
            // default settings
            'simple2' => [
                'manageStock' => true,
                'cartMinQty' => 1,
                'cartMaxQty' => 10000,
                'backorders' => 'no',
                'enableQtyIncrements' => false,
                'qtyIncrements' => 1
            ],
        ];

        return $this->jsonSerializer->serialize($stockItem[$product->getSku()] ?? []);
    }
}
