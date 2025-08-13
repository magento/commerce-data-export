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

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory as ResourceRuleFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Check prices for single (non-complex) products
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class ExportSingleProductPriceTest extends AbstractProductPriceTestHelper
{
    /**
     * @var CatalogRuleRepositoryInterface $catalogRuleRepository
     */
    private CatalogRuleRepositoryInterface $catalogRuleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalogRuleRepository = Bootstrap::getObjectManager()->get(CatalogRuleRepositoryInterface::class);
    }

    /**
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
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products.php
     * @dataProvider expectedSimpleProductPricesAfterDeleteDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws NoSuchEntityException
     */
    public function testExportDeletedSimpleProductsPrices(array $expectedSimpleProductPrices): void
    {
        // Delete product with regular price
        $skuToDelete = 'simple_product_with_regular_price';
        $deletedProductId = $this->productRepository->get($skuToDelete)->getId();
        $this->deleteProduct($skuToDelete);

        $this->checkExportedDeletedItems([$deletedProductId]);
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products.php
     * @dataProvider expectedSimpleProductPricesReplaceSkuDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws InputException
     * @throws StateException
     */
    public function testExportSimpleProductsPricesReplaceSku(array $expectedSimpleProductPrices): void
    {
        // Delete product with regular price
        $skuToReplace = 'simple_product_with_regular_price';
        $this->deleteProduct($skuToReplace);

        // Replace special price product with regular price product sku
        $productToChange = $this->productRepository->get('simple_product_with_special_price');
        $productToChange->setSku($skuToReplace);
        $this->productRepository->save($productToChange);
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products.php
     * @magentoDataFixture Magento/CatalogRule/_files/catalog_rule_25_customer_group_all.php
     * @dataProvider expectedSimpleProductPricesWithCatalogRuleDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws NoSuchEntityException
     */
    public function testExportSimpleProductsWithCatalogPriceRulePrices(array $expectedSimpleProductPrices): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products.php
     * @magentoDataFixture Magento/CatalogRule/_files/catalog_rule_25_customer_group_all.php
     * @dataProvider expectedSimpleProductPricesWithCatalogRuleDisabledDataProvider
     * @param array $expectedSimpleProductPrices
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function testExportSimpleProductsWithDisabledCatalogPriceRulePrices(array $expectedSimpleProductPrices): void
    {
        $ruleProductProcessor = Bootstrap::getObjectManager()->get(RuleProductProcessor::class);
        $rule = $this->getRuleByName('Test Catalog Rule With 25 Percent Off');
        $rule->setIsActive(0);
        $this->catalogRuleRepository->save($rule);
        $ruleProductProcessor->getIndexer()->reindexAll();
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices);
    }

    /**
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
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_with_tier_prices.php
     * @dataProvider expectedSimpleProductWithTierPricesDataProvider
     * @param array $expectedSimpleProductWithTierPrices
     * @return void
     * @throws NoSuchEntityException
     */
    public function testExportSimpleProductsWithTierPrices(array $expectedSimpleProductWithTierPrices): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductWithTierPrices);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_with_tier_and_group_prices.php
     * @dataProvider expectedSimpleProductWithGroupAndTierPricesDataProvider
     * @param array $expectedSimpleProductWithGroupAndTierPrices
     * @return void
     * @throws NoSuchEntityException
     */
    public function testExportSimpleProductsWithGroupedAndTierPrices(
        array $expectedSimpleProductWithGroupAndTierPrices
    ): void {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductWithGroupAndTierPrices);
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_with_tier_and_group_prices.php
     * @magentoDataFixture Magento/CatalogRule/_files/catalog_rule_25_customer_group_all.php
     * @dataProvider expectedSimpleProductWithTierPricesAndCatalogRulesDataProvider
     * @param array $expectedSimpleProductWithGroupAndTierPricesAndCatalogRules
     * @return void
     * @throws NoSuchEntityException
     */
    public function testExportSimpleProductsWithGroupedAndTierPricesAndCatalogRules(
        array $expectedSimpleProductWithGroupAndTierPricesAndCatalogRules
    ): void {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductWithGroupAndTierPricesAndCatalogRules);
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
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false
                    ],
                    'simple_product_with_regular_price_test_0' => [
                        'sku' => 'simple_product_with_regular_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false
                    ],
                    'simple_product_with_special_price_base_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false,
                    ],
                    'simple_product_with_special_price_test_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false,
                    ],
                    'virtual_product_with_special_price_base_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'virtual_product_with_special_price_test_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 10]],
                        'deleted' => false                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'deleted' => false
                    ],
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleProductPricesAfterDeleteDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_special_price_base_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false,
                    ],
                    'simple_product_with_special_price_test_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false,
                    ],
                    'virtual_product_with_special_price_base_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'virtual_product_with_special_price_test_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 10]],
                        'deleted' => false                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'deleted' => false
                    ],
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleProductPricesReplaceSkuDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_special_price_base_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => true,
                    ],
                    'simple_product_with_special_price_test_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => true,
                    ],
                    'simple_product_with_regular_price_base_0' => [
                        'sku' => 'simple_product_with_regular_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false,
                    ],
                    'simple_product_with_regular_price_test_0' => [
                        'sku' => 'simple_product_with_regular_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false,
                    ]
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function expectedSimpleProductPricesWithCatalogRuleDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_regular_price_base_0' => [
                        'sku' => 'simple_product_with_regular_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false
                    ],
                    'simple_product_with_regular_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_regular_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'discounts' => [0 => ['code' => 'catalog_rule', 'price' => 41.66]],
                        'deleted' => false
                    ],
                    'simple_product_with_regular_price_test_0' => [
                        'sku' => 'simple_product_with_regular_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false
                    ],
                    'simple_product_with_special_price_base_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'simple_product_with_special_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [
                            0 => ['code' => 'special_price', 'price' => 55.55],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
                        'deleted' => false
                    ],
                    'simple_product_with_special_price_test_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'virtual_product_with_special_price_base_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'virtual_product_with_special_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [
                            0 => ['code' => 'special_price', 'price' => 55.55],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
                        'deleted' => false
                    ],
                    'virtual_product_with_special_price_test_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 10]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [
                            0 => ['code' => 'group', 'price' => 15.15],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'deleted' => false
                    ]
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function expectedSimpleProductPricesWithCatalogRuleDisabledDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_regular_price_base_0' => [
                        'sku' => 'simple_product_with_regular_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false,
                    ],
                    'simple_product_with_regular_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_regular_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'discounts' => [0 => ['code' => 'catalog_rule', 'price' => 41.66]],
                        'deleted' => true,
                    ],
                    'simple_product_with_regular_price_test_0' => [
                        'sku' => 'simple_product_with_regular_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false
                    ],
                    'simple_product_with_special_price_base_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'simple_product_with_special_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [
                            0 => ['code' => 'special_price', 'price' => 55.55],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
                        'deleted' => true
                    ],
                    'simple_product_with_special_price_test_0' => [
                        'sku' => 'simple_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'virtual_product_with_special_price_base_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'virtual_product_with_special_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [
                            0 => ['code' => 'special_price', 'price' => 55.55],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
                        'deleted' => true
                    ],
                    'virtual_product_with_special_price_test_0' => [
                        'sku' => 'virtual_product_with_special_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_base_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 10]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [
                            0 => ['code' => 'group', 'price' => 15.15]
                        ],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_test_0' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_price',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'deleted' => false
                    ]
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
                        'type' => 'DOWNLOADABLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false
                    ],
                    'downloadable_product_with_regular_price_test_0' => [
                        'sku' => 'downloadable_product_with_regular_price',
                        'type' => 'DOWNLOADABLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'discounts' => null,
                        'deleted' => false
                    ],
                    'downloadable_product_with_special_price_base_0' => [
                        'sku' => 'downloadable_product_with_special_price',
                        'type' => 'DOWNLOADABLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 150.15,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'downloadable_product_with_special_price_test_0' => [
                        'sku' => 'downloadable_product_with_special_price',
                        'type' => 'DOWNLOADABLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 150.15,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'deleted' => false
                    ],
                    'downloadable_product_with_tier_price_base_0' => [
                        'sku' => 'downloadable_product_with_tier_price',
                        'type' => 'DOWNLOADABLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 150.15,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'deleted' => false
                    ],
                    'downloadable_product_with_tier_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'downloadable_product_with_tier_price',
                        'type' => 'DOWNLOADABLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 150.15,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'deleted' => false
                    ],
                    'downloadable_product_with_tier_price_test_0' => [
                        'sku' => 'downloadable_product_with_tier_price',
                        'type' => 'DOWNLOADABLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 150.15,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'deleted' => false
                    ],
                    'downloadable_product_with_tier_price_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'downloadable_product_with_tier_price',
                        'type' => 'DOWNLOADABLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 150.15,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'deleted' => false
                    ],
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    private function expectedSimpleProductWithGroupAndTierPricesDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_tier_and_grouped_prices_base_0' => [
                        'sku' => 'simple_product_with_tier_and_grouped_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 10]],
                        'tierPrices' => [0 => ['qty' => 2, 'percentage' => 20]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_and_grouped_prices_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_and_grouped_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [
                            0 => ['code' => 'group', 'percentage' => 10]
                        ],
                        'tierPrices' => [
                            ['qty' => 2, 'percentage' => 20],
                            ['qty' => 3, 'price' => 15.15],
                            ['qty' => 4, 'price' => 14.15],
                        ],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_and_grouped_prices_test_0' => [
                        'sku' => 'simple_product_with_tier_and_grouped_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.14]],
                        'tierPrices' => [0 => ['qty' => 2, 'price' => 14.14]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_and_grouped_prices_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_and_grouped_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.14]],
                        'tierPrices' => [
                            ['qty' => 2, 'price' => 14.14],
                            ['qty' => 3, 'price' => 13.13],
                            ['qty' => 4, 'price' => 12.13],
                        ],
                        'deleted' => false
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    private function expectedSimpleProductWithTierPricesDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_tier_prices_base_0' => [
                        'sku' => 'simple_product_with_tier_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => null,
                        'tierPrices' => [0 => ['qty' => 2, 'percentage' => 20]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_prices_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => null,
                        'tierPrices' => [
                            ['qty' => 2, 'percentage' => 20],
                            ['qty' => 3, 'price' => 15.15],
                            ['qty' => 4, 'price' => 14.15],
                        ],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_prices_test_0' => [
                        'sku' => 'simple_product_with_tier_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => null,
                        'tierPrices' => [0 => ['qty' => 2, 'price' => 14.14]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_prices_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => null,
                        'tierPrices' => [
                            ['qty' => 2, 'price' => 14.14],
                            ['qty' => 3, 'price' => 13.13],
                            ['qty' => 4, 'price' => 12.13],
                        ],
                        'deleted' => false
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array[]
     */
    private function expectedSimpleProductWithTierPricesAndCatalogRulesDataProvider(): array
    {
        return [
            [
                [
                    'simple_product_with_tier_and_grouped_prices_base_0' => [
                        'sku' => 'simple_product_with_tier_and_grouped_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [
                            ['code' => 'group', 'percentage' => 10]
                        ],
                        'tierPrices' => [0 => ['qty' => 2, 'percentage' => 20]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_and_grouped_prices_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_and_grouped_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'discounts' => [
                            ['code' => 'group', 'percentage' => 10],
                            ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
                        'tierPrices' => [
                            ['qty' => 2, 'percentage' => 20],
                            ['qty' => 3, 'price' => 15.15],
                            ['qty' => 4, 'price' => 14.15],
                        ],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_and_grouped_prices_test_0' => [
                        'sku' => 'simple_product_with_tier_and_grouped_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [
                            ['code' => 'group', 'price' => 15.14]
                        ],
                        'tierPrices' => [0 => ['qty' => 2, 'price' => 14.14]],
                        'deleted' => false
                    ],
                    'simple_product_with_tier_and_grouped_prices_test_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_tier_and_grouped_prices',
                        'type' => 'SIMPLE',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'discounts' => [
                            ['code' => 'group', 'price' => 15.14]
                        ],
                        'tierPrices' => [
                            ['qty' => 2, 'price' => 14.14],
                            ['qty' => 3, 'price' => 13.13],
                            ['qty' => 4, 'price' => 12.13],
                        ],
                        'deleted' => false
                    ]
                ]
            ]
        ];
    }

    /**
     * Retrieve catalog rule by name from db.
     *
     * @param string $name
     * @return RuleInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function getRuleByName(string $name): RuleInterface
    {
        $catalogRuleResource = Bootstrap::getObjectManager()->get(ResourceRuleFactory::class)->create();
        $select = $catalogRuleResource->getConnection()->select();
        $select->from($catalogRuleResource->getMainTable(), RuleInterface::RULE_ID);
        $select->where(RuleInterface::NAME . ' = ?', $name);
        $ruleId = $catalogRuleResource->getConnection()->fetchOne($select);

        return $this->catalogRuleRepository->get((int)$ruleId);
    }
}
