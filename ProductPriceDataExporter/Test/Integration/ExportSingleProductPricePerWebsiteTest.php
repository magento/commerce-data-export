<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Test\Integration;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Check prices for single (non-complex) products
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class ExportSingleProductPricePerWebsiteTest extends AbstractProductPriceTestHelper
{
    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products.php
     * @dataProvider expectedSimpleProductPricesDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws NoSuchEntityException
     */
    public function testExportSimpleProductsPrices(array $expectedSimpleProductPrices): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/downloadable_products.php
     * @dataProvider expectedDownloadableProductPricesDataProvider
     * @param array $expectedDownloadableProductPricesDataProvider
     * @throws NoSuchEntityException
     */
    public function testExportDownloadableProductsPrices(array $expectedDownloadableProductPricesDataProvider): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedDownloadableProductPricesDataProvider);
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleProductPricesDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_regular_price_base_0' => [
                        'sku' => 'simple_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 50.5,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_regular_price_test_0' => [
                        'sku' => 'simple_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_special_price_base_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 50.5]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_special_price_test_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'type' => 'SIMPLE'
                    ],
                    'virtual_product_with_special_price_base_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 50.5]],
                        'type' => 'SIMPLE'
                    ],
                    'virtual_product_with_special_price_test_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 10]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'type' => 'SIMPLE'
                    ],
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    private function expectedDownloadableProductPricesDataProvider(): array
    {
        return [
            [
                [
                    'downloadable_product_with_regular_price_base_0' => [
                        'sku' => 'downloadable_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 50.5,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'DOWNLOADABLE'
                    ],
                    'downloadable_product_with_regular_price_test_0' => [
                        'sku' => 'downloadable_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'DOWNLOADABLE'
                    ],
                    'downloadable_product_with_special_price_base_0' => [
                        'sku' => 'downloadable_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 150.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 50.5]],
                        'type' => 'DOWNLOADABLE'
                    ],
                    'downloadable_product_with_special_price_test_0' => [
                        'sku' => 'downloadable_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 150.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'type' => 'DOWNLOADABLE'
                    ],
                    'downloadable_product_with_tier_price_base_0' => [
                        'sku' => 'downloadable_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 150.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'DOWNLOADABLE'
                    ],
                    'downloadable_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'downloadable_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 150.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'DOWNLOADABLE'
                    ],
                    'downloadable_product_with_tier_price_test_0' => [
                        'sku' => 'downloadable_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 150.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'type' => 'DOWNLOADABLE'
                    ],
                    'downloadable_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'downloadable_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 150.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'type' => 'DOWNLOADABLE'
                    ],
                ]
            ]
        ];
    }
}
