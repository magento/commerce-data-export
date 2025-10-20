<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\BundleProductDataExporter\Test\Integration;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Model\Option;
use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for bundle product export
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class BundleProductTest extends AbstractProductTestHelper
{
    private const BUNDLE_SKU = 'bundle-product';
    private const DYNAMIC_BUNDLE_SKU = 'dynamic_bundle_product_with_special_price';

    /**
     * @var ArrayUtils
     */
    private $arrayUtils;

    /**
     * @inheritDoc
     */
    protected function setUp() : void
    {
        $this->arrayUtils = Bootstrap::getObjectManager()->create(ArrayUtils::class);

        parent::setUp();
    }

    /**
     * Validate bundle product options data
     * @param array $item
     * @magentoDataFixture Magento/Bundle/_files/product_1.php
     * @dataProvider getBundleFixedProductOptionsDataProvider
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     */
    public function testBundleFixedProductOptions(array $item) : void
    {
        $extractedProduct = $this->getExtractedProduct(self::BUNDLE_SKU, 'default');
        $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

        foreach ($item as $key => $expectedData) {
            $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProduct[$key]);
            self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        }
    }

    /**
     * Validate bundle product options data with option price different than default
     *
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_BundleProductDataExporter::Test/_files/product_with_no_website_option.php
     * @dataProvider getBundleFixedProductOptionsBeforeAndAfterPriceChangeDataProvider
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     *
     */
    public function testBundleFixedProductOptionsWithPriceDifferentThanDefault(
        array $beforeChanges,
        array $afterChanges
    ) : void {
        $product = $this->productRepository->get('bundle-product', true, 'main_website_store');

        $extractedProductBeforeChanges = $this->getExtractedProduct(self::BUNDLE_SKU, 'default');
        $this->assertNotEmpty($extractedProductBeforeChanges, 'Feed data must not be empty');

        foreach ($beforeChanges as $key => $expectedData) {
            $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProductBeforeChanges[$key]);
            self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        }

        $productExtensionAttributes = $product->getExtensionAttributes();
        $newPrice = 7.77;
            /** @var Option $optionData */
        foreach ($productExtensionAttributes->getBundleProductOptions() as $bundleOptionData) {
            /** @var LinkInterface $bundleSelection */
            foreach ($bundleOptionData->getProductLinks() as $bundleSelection) {
                if ($bundleSelection->getSku() === 'simple') {
                    $bundleSelection->setPrice($newPrice);
                }
            }
        }
        $product->setExtensionAttributes($productExtensionAttributes);
        $this->productRepository->save($product);
        $this->emulatePartialReindexBehavior([$product->getId()]);

        //Verify exported data after changes
        $extractedProductAfterChanges = $this->getExtractedProduct(self::BUNDLE_SKU, 'default');
        $this->assertNotEmpty($extractedProductAfterChanges, 'Feed data must not be empty');

        foreach ($afterChanges as $key => $expectedData) {
            $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProductAfterChanges[$key]);
            self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        }
    }

    /**
     * Validate bundle product options data
     * @param array $item
     * @return void
     * @throws \Zend_Db_Statement_Exception
     * @magentoDataFixture Magento/Bundle/_files/dynamic_bundle_product_with_special_price.php
     * @dataProvider getBundleDynamicProductOptionsDataProvider
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testBundleDynamicProductOptions(array $item) : void
    {
        $extractedProduct = $this->getExtractedProduct(self::DYNAMIC_BUNDLE_SKU, 'default');
        $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

        foreach ($item as $key => $expectedData) {
            $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProduct[$key]);
            self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        }
    }

    /**
     * Get bundle product options data provider
     *
     * @return array
     */
    public static function getBundleFixedProductOptionsDataProvider() : array
    {
        return [
            'bundleProduct' => [
                'item' => [
                    'feedData' => [
                        'sku' => self::BUNDLE_SKU,
                        'storeViewCode' => 'default',
                        'name' => 'Bundle Product',
                        'type' => 'bundle_fixed',
                        'optionsV2' => [
                            [
                                'type' => 'bundle',
                                'renderType' => 'select',
                                'required' => true,
                                'label' => 'Bundle Product Items',
                                'sortOrder' => 0,
                                'values' => [
                                    [
                                        'sortOrder' => 0,
                                        'label' => 'Simple Product',
                                        'qty' => 1,
                                        'isDefault' => false,
                                        'qtyMutability' => true,
                                        'sku' => 'simple',
                                        'price' => 2.75,
                                        'priceType' => 'fixed'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get bundle product options with store specific option prices data provider
     *
     * @return array
     */
    public static function getBundleFixedProductOptionsBeforeAndAfterPriceChangeDataProvider() : array
    {
        return [
            [
                'beforeChanges' => [
                    'feedData' => [
                        'sku' => self::BUNDLE_SKU,
                        'storeViewCode' => 'default',
                        'name' => 'Bundle Product',
                        'type' => 'bundle_fixed',
                        'optionsV2' => [
                            [
                                'type' => 'bundle',
                                'renderType' => 'select',
                                'required' => true,
                                'label' => 'Bundle Product Items',
                                'sortOrder' => 0,
                                'values' => [
                                    [
                                        'sortOrder' => 0,
                                        'label' => 'Simple Product',
                                        'qty' => 1,
                                        'isDefault' => false,
                                        'qtyMutability' => true,
                                        'sku' => 'simple',
                                        'price' => 2.75,
                                        'priceType' => 'fixed'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'afterChanges' => [
                    'feedData' => [
                        'sku' => self::BUNDLE_SKU,
                        'storeViewCode' => 'default',
                        'name' => 'Bundle Product',
                        'type' => 'bundle_fixed',
                        'optionsV2' => [
                            [
                                'type' => 'bundle',
                                'renderType' => 'select',
                                'required' => true,
                                'label' => 'Bundle Product Items',
                                'sortOrder' => 0,
                                'values' => [
                                    [
                                        'sortOrder' => 0,
                                        'label' => 'Simple Product',
                                        'qty' => 1,
                                        'isDefault' => false,
                                        'qtyMutability' => true,
                                        'sku' => 'simple',
                                        'price' => 7.77,
                                        'priceType' => 'fixed'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get bundle product options data provider
     *
     * @return array
     */
    public static function getBundleDynamicProductOptionsDataProvider() : array
    {
        return [
            'bundleProduct' => [
                'item' => [
                    'feedData' => [
                        'sku' => self::DYNAMIC_BUNDLE_SKU,
                        'storeViewCode' => 'default',
                        'name' => 'Bundle Product',
                        'type' => 'bundle',
                        'optionsV2' => [
                            [
                                'type' => 'bundle',
                                'renderType' => 'select',
                                'required' => true,
                                'label' => 'Option 1',
                                'sortOrder' => 0,
                                'values' => [
                                    [
                                        'sortOrder' => 0,
                                        'label' => 'Simple Product With Price 10',
                                        'qty' => 1,
                                        'isDefault' => false,
                                        'qtyMutability' => false,
                                        'sku' => 'simple1000',
                                        'price' => 0,
                                        'priceType' => 'fixed'
                                    ],
                                    [
                                        'sortOrder' => 0,
                                        'label' => 'Simple Product With Price 20',
                                        'qty' => 1,
                                        'isDefault' => false,
                                        'qtyMutability' => false,
                                        'sku' => 'simple1001',
                                        'price' => 0,
                                        'priceType' => 'fixed'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
