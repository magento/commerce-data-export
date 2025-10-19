<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Test\Integration;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreIsInactiveException;

/**
 * Check prices for single (non-complex) products with update operations
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class ExportSingleProductPriceUpdateOperationsTest extends AbstractProductPriceTestHelper
{
    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/catalog_data_exporter_product_prices_indexer_update_on_schedule.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_all_websites_grouped_price.php
     * @dataProvider expectedSimpleProductPricesUnassignedWebsiteDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    public function testUnassignProductFromWebsite(array $expectedSimpleProductPrices): void
    {
        $product = $this->productRepository->get('simple_product_with_tier_price');
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $secondWebsiteId = $websiteRepository->get('test')->getId();
        $product->setWebsiteIds([$secondWebsiteId]);
        $this->productRepository->save($product);
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/catalog_data_exporter_product_prices_indexer_update_on_schedule.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_all_websites_grouped_price.php
     * @dataProvider expectedSimpleProductDisabledGlobalDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function testDisableProductGlobally(array $expectedSimpleProductPrices): void
    {
        //Get product for edit in general scope (all websites)
        $product = $this->productRepository->get('simple_product_with_tier_price', true, 0);
        //Disable it on general level
        $product->setStatus(2);
        $this->productRepository->save($product);
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/catalog_data_exporter_product_prices_indexer_update_on_schedule.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_all_websites_grouped_price.php
     * @dataProvider expectedSimpleProductEnabledOneStoreDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws StoreIsInactiveException
     */
    public function testEnableProductOnWebsite(array $expectedSimpleProductPrices): void
    {
        //Get product for edit in general scope (all websites)
        $product = $this->productRepository->get('simple_product_with_tier_price', true, 0);
        //Disable it on general level
        $product->setStatus(2);
        $this->productRepository->save($product);
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);
        $store = $storeRepository->getActiveStoreByCode('fixture_second_store');
        $secondWebsiteStoreId = $store->getId();
        //Get product for edit in general scope (all websites)
        $secondStoreProduct = $this->productRepository->get(
            'simple_product_with_tier_price',
            true,
            $secondWebsiteStoreId
        );
        //Enable for second store
        $secondStoreProduct->setStatus(1);
        $this->productRepository->save($secondStoreProduct);
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/catalog_data_exporter_product_prices_indexer_update_on_schedule.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_all_websites_grouped_price.php
     * @dataProvider expectedSimpleProductEnabledOneStoreDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws StoreIsInactiveException
     */
    public function testUpdateProductPriceOnSecondStore(array $expectedSimpleProductPrices): void
    {
        //Get product for edit in general scope (all websites)
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);
        $store = $storeRepository->getActiveStoreByCode('fixture_second_store');
        $secondWebsiteStoreId = $store->getId();
        //Get product for edit in general scope (all websites)
        $secondStoreProduct = $this->productRepository->get(
            'simple_product_with_tier_price',
            true,
            $secondWebsiteStoreId
        );
        //Change price for second store
        $secondStoreProduct->setPrice('30.5');
        $this->productRepository->save($secondStoreProduct);
        $this->changeExpectedPriceForWebsite($expectedSimpleProductPrices, 'test', '30.5');
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
        //Change price for second store again
        $secondStoreProduct->setPrice('20.5');
        $this->changeExpectedPriceForWebsite($expectedSimpleProductPrices, 'test', '20.5');
        $this->productRepository->save($secondStoreProduct);

        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/catalog_data_exporter_product_prices_indexer_update_on_schedule.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_all_websites_grouped_price.php
     * @dataProvider expectedSimpleProductPricesReassignProductsToWebsiteDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function testReassignProductToWebsite(array $expectedSimpleProductPrices): void
    {
        $product = $this->productRepository->get('simple_product_with_tier_price');
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $firstWebsiteId = $websiteRepository->get('base')->getId();
        $secondWebsiteId = $websiteRepository->get('test')->getId();
        $product->setWebsiteIds([$secondWebsiteId]);
        $this->productRepository->save($product);
        $product->setWebsiteIds([$firstWebsiteId, $secondWebsiteId]);
        $this->productRepository->save($product);

        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/catalog_data_exporter_product_prices_indexer_update_on_schedule.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products.php
     * @dataProvider expectedSimpleProductPricesUnassignedGroupPriceDataProvider
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testUnassignGroupPriceFromProduct(array $expectedSimpleProductPrices): void
    {
        //TODO: Need to be covered
    }

    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/catalog_data_exporter_product_prices_indexer_update_on_schedule.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products.php
     * @dataProvider expectedSimpleProductPricesReassignGroupPricesDataProvider
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testReassignGroupPriceToProduct(array $expectedSimpleProductPrices): void
    {
        //TODO: Need to be covered
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleProductPricesUnassignedWebsiteDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => true,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => true,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    private function expectedSimpleProductDisabledGlobalDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    private function expectedSimpleProductEnabledOneStoreDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleProductPricesReassignProductsToWebsiteDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleProductPricesUnassignedGroupPriceDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => true,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => true,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleProductPricesReassignGroupPricesDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
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
     * @param array $expectedSimpleProductPrices
     * @param string $websiteCode
     * @param string $price
     * @return array
     */
    private function changeExpectedPriceForWebsite(
        array &$expectedSimpleProductPrices,
        string $websiteCode,
        string $price
    ): array {
        foreach ($expectedSimpleProductPrices as &$expectedSimpleProductPrice) {
            if ($expectedSimpleProductPrice['websiteCode'] === $websiteCode) {
                $expectedSimpleProductPrice['regular'] = $price;
            }
        }

        return $expectedSimpleProductPrices;
    }
}
