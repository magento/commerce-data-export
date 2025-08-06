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

namespace Magento\ProductPriceDataExporter\Test\Integration;

use Magento\Framework\Exception\NoSuchEntityException;
use Zend_Db_Statement_Exception;

/**
 * Check prices for complex products
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class ExportComplexProductPriceTest extends AbstractProductPriceTestHelper
{
    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/bundle_fixed_products.php
     * @dataProvider expectedBundleFixedProductPricesDataProvider
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportBundleFixedProductsPrices(array $expectedBundleFixedProductPricesDataProvider): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedBundleFixedProductPricesDataProvider);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/bundle_dynamic_products.php
     * @dataProvider expectedBundleDynamicProductPricesDataProvider
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportBundleDynamicProductsPrices(array $expectedBundleDynamicProductPricesDataProvider): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedBundleDynamicProductPricesDataProvider);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configurable_regular_price_products.php
     * @dataProvider expectedConfigurableRegularProductPricesDataProvider
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportConfigurableProductsRegularPrices(array $expectedProductPricesDataProvider): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedProductPricesDataProvider);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configurable_special_and_tier_price_products.php
     * @dataProvider expectedConfigurableSpecialAndTierProductPricesDataProvider
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportConfigurableProductsSpecialAndTierPrices(array $expectedProductPricesDataProvider): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedProductPricesDataProvider);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/grouped_products_regular_prices.php
     * @dataProvider expectedGroupedProductRegularPriceDataProvider
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportGroupedProductsRegularPrices(array $expectedProductPricesDataProvider): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedProductPricesDataProvider);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/grouped_products_special_and_tier_prices.php
     * @dataProvider expectedGroupedSpecialAndTierProductPricesDataProvider
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportGroupedProductsSpecialAndTierPrices(array $expectedProductPricesDataProvider): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedProductPricesDataProvider);
    }

    /**
     * @return array[]
     */
    private function expectedBundleFixedProductPricesDataProvider(): array
    {
        return [
            [
                [
                    'bundle_fixed_product_with_regular_price_base_0' => [
                        'sku' => 'bundle_fixed_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 105.1,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'BUNDLE_FIXED'
                    ],
                    'bundle_fixed_product_with_regular_price_test_0' => [
                        'sku' => 'bundle_fixed_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 105.1,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'BUNDLE_FIXED'
                    ],
                    'bundle_fixed_product_with_special_price_base_0' => [
                        'sku' => 'bundle_fixed_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 155.15,
                        'discounts' => [0 => ['code' => 'special_price', 'percentage' => 55.55]],
                        'deleted' => false,
                        'type' => 'BUNDLE_FIXED'
                    ],
                    'bundle_fixed_product_with_special_price_test_0' => [
                        'sku' => 'bundle_fixed_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 155.15,
                        'discounts' => [0 => ['code' => 'special_price', 'percentage' => 55.55]],
                        'deleted' => false,
                        'type' => 'BUNDLE_FIXED'
                    ],
                    'bundle_fixed_product_with_tier_price_base_0' => [
                        'sku' => 'bundle_fixed_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 16.16]],
                        'deleted' => false,
                        'type' => 'BUNDLE_FIXED'
                    ],
                    'bundle_fixed_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'bundle_fixed_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 15.15]],
                        'tierPrices' => [0 => ['qty' => 2, 'percentage' => 14.14]],
                        'deleted' => false,
                        'type' => 'BUNDLE_FIXED'
                    ],
                    'bundle_fixed_product_with_tier_price_test_0' => [
                        'sku' => 'bundle_fixed_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 14.14]],
                        'deleted' => false,
                        'type' => 'BUNDLE_FIXED'
                    ],
                    'bundle_fixed_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'bundle_fixed_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 13.13]],
                        'tierPrices' => [0 => ['qty' => 2.55, 'percentage' => 12.12]],
                        'deleted' => false,
                        'type' => 'BUNDLE_FIXED'
                    ],
                ]
            ]
        ];
    }

    /**
     * @return array[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function expectedBundleDynamicProductPricesDataProvider(): array
    {
        return [
            [
                [
                    'simple_option_1_base_0' => [
                        'sku' => 'simple_option_1',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_regular_price'
                            ]
                        ],
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_1_test_0' => [
                        'sku' => 'simple_option_1',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_regular_price'
                            ]
                        ],
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_2_base_0' => [
                        'sku' => 'simple_option_2',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_regular_price'
                            ]
                        ],
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_2_test_0' => [
                        'sku' => 'simple_option_2',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_regular_price'
                            ]
                        ],
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_3_base_0' => [
                        'sku' => 'simple_option_3',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_special_price'
                            ]
                        ],
                        'regular' => 55.55,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 10.1]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_3_test_0' => [
                        'sku' => 'simple_option_3',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_special_price'
                            ]
                        ],
                        'regular' => 55.55,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 10.1]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_4_base_0' => [
                        'sku' => 'simple_option_4',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_special_price'
                            ]
                        ],
                        'regular' => 55.55,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 10.1]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_4_test_0' => [
                        'sku' => 'simple_option_4',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_special_price'
                            ]
                        ],
                        'regular' => 55.55,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 10.1]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_5_base_0' => [
                        'sku' => 'simple_option_5',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_tier_price'
                            ]
                        ],
                        'regular' => 20.20,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_5_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_option_5',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_tier_price'
                            ]
                        ],
                        'regular' => 20.20,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'tierPrices' => [0 => ['qty' => 2, 'price' => 14.14]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_5_test_0' => [
                        'sku' => 'simple_option_5',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_tier_price'
                            ]
                        ],
                        'regular' => 20.20,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_5_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_option_5',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_tier_price'
                            ]
                        ],
                        'regular' => 20.20,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'tierPrices' => [0 => ['qty' => 2.55, 'price' => 12.12]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'bundle_dynamic_product_with_regular_price_base_0' => [
                        'sku' => 'bundle_dynamic_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 0,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'BUNDLE_DYNAMIC'
                    ],
                    'bundle_dynamic_product_with_regular_price_test_0' => [
                        'sku' => 'bundle_dynamic_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 0,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'BUNDLE_DYNAMIC'
                    ],
                    'bundle_dynamic_product_with_special_price_base_0' => [
                        'sku' => 'bundle_dynamic_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 0,
                        'discounts' => [0 => ['code' => 'special_price', 'percentage' => 55.55]],
                        'deleted' => false,
                        'type' => 'BUNDLE_DYNAMIC'
                    ],
                    'bundle_dynamic_product_with_special_price_test_0' => [
                        'sku' => 'bundle_dynamic_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 0,
                        'discounts' => [0 => ['code' => 'special_price', 'percentage' => 55.55]],
                        'deleted' => false,
                        'type' => 'BUNDLE_DYNAMIC'
                    ],
                    'bundle_dynamic_product_with_tier_price_base_0' => [
                        'sku' => 'bundle_dynamic_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 0,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 16.16]],
                        'deleted' => false,
                        'type' => 'BUNDLE_DYNAMIC'
                    ],
                    'bundle_dynamic_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'bundle_dynamic_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 0,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 15.15]],
                        'tierPrices' => [0 => ['qty' => 2, 'price' => 14.14]],
                        'deleted' => false,
                        'type' => 'BUNDLE_DYNAMIC'
                    ],
                    'bundle_dynamic_product_with_tier_price_test_0' => [
                        'sku' => 'bundle_dynamic_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 0,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 14.14]],
                        'deleted' => false,
                        'type' => 'BUNDLE_DYNAMIC'
                    ],
                    'bundle_dynamic_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'bundle_dynamic_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 0,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 13.13]],
                        'tierPrices' => [0 => ['qty' => 2.55, 'price' => 12.12]],
                        'deleted' => false,
                        'type' => 'BUNDLE_DYNAMIC'
                    ],
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    private function expectedConfigurableSpecialAndTierProductPricesDataProvider(): array
    {
        return [
            [
                [
                    'simple_option_1_base_0' => [
                        'sku' => 'simple_option_1',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 155.15,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 15.15]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_1_test_0' => [
                        'sku' => 'simple_option_1',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 155.15,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 15.15]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_2_base_0' => [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 150,
                        'discounts' => null,
                        'tierPrices' => [0 => ['qty' => 2, 'price' => 16.16]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_2_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 150,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'tierPrices' => [0 => ['qty' => 2, 'price' => 16.16]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_2_test_0' => [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 150,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_2_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 150,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'tierPrices' => [0 => ['qty' => 2.55, 'price' => 13.13]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    private function expectedConfigurableRegularProductPricesDataProvider(): array
    {
        return [
            [
                [
                    'simple_option_1_base_0' => [
                        'sku' => 'simple_option_1',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_1_test_0' => [
                        'sku' => 'simple_option_1',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_2_base_0' => [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 105.1,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_option_2_test_0' => [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 105.1,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    private function expectedGroupedProductRegularPriceDataProvider(): array
    {
        return [
            [
                [
                    'simple_base_0' => [
                        'sku' => 'simple',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_test_0' => [
                        'sku' => 'simple',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'virtual-product_base_0' => [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 155.15,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'virtual-product_test_0' => [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 155.15,
                        'discounts' => null,
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ]
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    private function expectedGroupedSpecialAndTierProductPricesDataProvider(): array
    {
        return [
            [
                [
                    'simple_base_0' => [
                        'sku' => 'simple',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 155.15,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 15.15]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'simple_test_0' => [
                        'sku' => 'simple',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 155.15,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 15.15]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'virtual-product_base_0' => [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 10,
                        'discounts' => null,
                        'tierPrices' => [0 => ['qty' => 2, 'price' => 16.16]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'virtual-product_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 10,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'tierPrices' => [0 => ['qty' => 2, 'price' => 16.16]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'virtual-product_test_0' => [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 10,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                    'virtual-product_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 10,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'tierPrices' => [0 => ['qty' => 2.55, 'price' => 13.13]],
                        'deleted' => false,
                        'type' => 'SIMPLE'
                    ],
                ]
            ]
        ];
    }
}
