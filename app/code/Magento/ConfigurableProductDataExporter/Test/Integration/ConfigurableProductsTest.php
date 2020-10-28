<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProductDataExporter\Model\Provider\Product\ConfigurableAttributeUid;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for configurable product export
 *
 */
class ConfigurableProductsTest extends AbstractProductTestHelper
{
    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var ConfigurableAttributeUid
     */
    private $configurableAttributeUid;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->configurable = Bootstrap::getObjectManager()->create(Configurable::class);
        $this->configurableAttributeUid = Bootstrap::getObjectManager()->create(ConfigurableAttributeUid::class);
    }

    /**
     * Validate configurable product data
     *
     * @magentoDataFixture Magento/ConfigurableProductDataExporter/_files/setup_configurable_products.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testConfigurableProducts() : void
    {
        $skus = ['configurable1'];
        $storeViewCodes = ['default', 'fixture_second_store'];
        $attributeCodes = ['test_configurable'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateBaseProductData($product, $extractedProduct, $storeViewCode);
                $this->validateCategoryData($product, $extractedProduct);
                $this->validatePricingData($product, $extractedProduct);
                $this->validateImageUrls($product, $extractedProduct);
                $this->validateAttributeData($product, $extractedProduct);
                $this->validateOptionsData($product, $extractedProduct);
                $this->validateVariantsData($product, $extractedProduct, $attributeCodes);
            }
        }
    }

    /**
     * Validate parent product data
     *
     * @magentoDataFixture Magento/ConfigurableProductDataExporter/_files/setup_configurable_products.php
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testParentProducts() : void
    {
        $this->runIndexer([40, 50, 60, 70]);

        $skus = ['simple_option_50', 'simple_option_60', 'simple_option_70'];
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Configurable::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateParentData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate product's parent data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     * @throws NoSuchEntityException
     */
    private function validateParentData(ProductInterface $product, array $extractedProduct) : void
    {
        $parents = [];
        $parentIds = $this->configurable->getParentIdsByChild($product->getId());
        foreach ($parentIds as $parentId) {
            $parentProduct = $this->productRepository->getById($parentId);
            $parents[] = [
                'sku' => $parentProduct->getSku(),
                'productType' => $parentProduct->getTypeId()
            ];
        }
        $this->assertEquals($parents, $extractedProduct['feedData']['parents']);
    }

    /**
     * Validate product options in extracted product data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     */
    private function validateOptionsData(ProductInterface $product, array $extractedProduct) : void
    {
        $productOptions = $product->getExtensionAttributes()->getConfigurableProductOptions();
        $expectedOptions = [];
        foreach ($productOptions as $productOption) {
            $expectedOptions[$productOption['product_attribute']['attribute_code']] = [
                'id' => $productOption['product_attribute']['attribute_code'],
                'type' => 'configurable',
                'label' => 'Test Configurable',
                'sort_order' => 0,
                'values' => $this->getOptionValues($productOption->getAttributeId(), $productOption->getOptions()),
            ];
        }

        $this->assertCount(count($expectedOptions), $extractedProduct['feedData']['productOptions']);
        foreach ($extractedProduct['feedData']['productOptions'] as $extractedOption) {
            $optionId = $extractedOption['id'];
            $this->assertEquals($expectedOptions[$optionId]['id'], $optionId);
            $this->assertEquals($expectedOptions[$optionId]['type'], $extractedOption['type']);
            $this->assertEquals($expectedOptions[$optionId]['label'], $extractedOption['label']);
            $this->assertEquals($expectedOptions[$optionId]['sort_order'], $extractedOption['sort_order']);
            $this->assertCount(count($expectedOptions[$optionId]['values']), $extractedOption['values']);

            foreach ($extractedOption['values'] as $value) {
                $valueId = $value['id'];
                $this->assertEquals($expectedOptions[$optionId]['values'][$valueId]['id'], $valueId);
                $this->assertEquals(
                    $expectedOptions[$optionId]['values'][$valueId]['label'],
                    $value['label']
                );
            }
        }
    }

    /**
     * Validate product variants in extracted product data
     *
     * @param ProductInterface $product
     * @param array $extract
     * @param array $attributeCodes
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    private function validateVariantsData(ProductInterface $product, array $extract, array $attributeCodes) : void
    {
        $childIds = $product->getExtensionAttributes()->getConfigurableProductLinks();
        $variants = [];
        foreach ($childIds as $childId) {
            $childProduct = $this->productRepository->getById($childId);
            $childProductPricing = $this->getPricingData($childProduct);
            $variants[] = [
                'sku' => $childProduct->getSku(),
                'minimumPrice' => [
                    'regularPrice' => $childProductPricing['price'],
                    'finalPrice' => $childProductPricing['final_price']
                ],
                'selections' => $this->getVariantSelections($childProduct, $attributeCodes)
            ];
        }
        $this->assertEquals($variants, $extract['feedData']['variants']);
    }

    /**
     * Get partially hardcoded option values to compare to extracted product data
     *
     * @param array $optionValues
     * @return array
     */
    private function getOptionValues(string $attributeId, array $optionValues) : array
    {
        $values = [];
        $i = 1;
        foreach ($optionValues as $optionValue) {
            $id = $this->configurableAttributeUid->resolve($attributeId, $optionValue['value_index']);
            $values[$id] = [
                'id' => $id,
                'label' => 'Option ' . $i,
            ];
            $i++;
        }
        return $values;
    }

    /**
     * Get variant selections data
     *
     * @param ProductInterface $childProduct
     * @param array $attributeCodes
     * @return array
     */
    private function getVariantSelections(ProductInterface $childProduct, array $attributeCodes) : array
    {
        $selections = [];
        foreach ($attributeCodes as $attributeCode) {
            $selections[] = [
                'name' => $childProduct->getAttributes()[$attributeCode]->getStoreLabel(),
                'value' => $childProduct->getAttributeText($attributeCode)
            ];
        }
        return $selections;
    }
}
