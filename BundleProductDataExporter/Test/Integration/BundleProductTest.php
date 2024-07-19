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

namespace Magento\BundleProductDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for bundle product export
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
     *
     * @param array $bundleProductOptionsDataProvider
     *
     * @magentoDataFixture Magento/Bundle/_files/product_1.php
     * @dataProvider getBundleFixedProductOptionsDataProvider
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     *
     * @return void
     */
    public function testBundleFixedProductOptions(array $bundleProductOptionsDataProvider) : void
    {
        $extractedProduct = $this->getExtractedProduct(self::BUNDLE_SKU, 'default');
        $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

        foreach ($bundleProductOptionsDataProvider as $key => $expectedData) {
            $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProduct[$key]);
            self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        }
    }

    /**
     * Validate bundle product options data
     *
     * @param array $bundleProductOptionsDataProvider
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     * @magentoDataFixture Magento/Bundle/_files/dynamic_bundle_product_with_special_price.php
     * @dataProvider getBundleDynamicProductOptionsDataProvider
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     *
     */
    public function testBundleDynamicProductOptions(array $bundleProductOptionsDataProvider) : void
    {
        $extractedProduct = $this->getExtractedProduct(self::DYNAMIC_BUNDLE_SKU, 'default');
        $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

        foreach ($bundleProductOptionsDataProvider as $key => $expectedData) {
            $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProduct[$key]);
            self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        }
    }

    /**
     * Get bundle product options data provider
     *
     * @return array
     */
    public function getBundleFixedProductOptionsDataProvider() : array
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
     * Get bundle product options data provider
     *
     * @return array
     */
    public function getBundleDynamicProductOptionsDataProvider() : array
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
