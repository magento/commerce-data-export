<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Test\Integration;

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory as ResourceRuleFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
        $affectedIds = [];
        foreach ($expectedDownloadableProductPricesDataProvider as $expectedItem) {
            $affectedIds[] = $this->productRepository->get($expectedItem['sku'])->getId();
        }
        $this->runIndexer($affectedIds);
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
                        'regular' => 55.55,
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
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
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
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
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
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_regular_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_regular_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'catalog_rule', 'price' => 41.66]],
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
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_special_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_special_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [
                            0 => ['code' => 'special_price', 'price' => 55.55],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
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
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'type' => 'SIMPLE'
                    ],
                    'virtual_product_with_special_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'virtual_product_with_special_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [
                            0 => ['code' => 'special_price', 'price' => 55.55],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
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
                        'discounts' => [
                            0 => ['code' => 'group', 'price' => 15.15],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
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
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_regular_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_regular_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => true,
                        'discounts' => [0 => ['code' => 'catalog_rule', 'price' => 41.66]],
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
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'type' => 'SIMPLE'
                    ],
                    'simple_product_with_special_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'simple_product_with_special_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => true,
                        'discounts' => [
                            0 => ['code' => 'special_price', 'price' => 55.55],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
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
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
                        'type' => 'SIMPLE'
                    ],
                    'virtual_product_with_special_price_base_b6589fc6ab0dc82cf12099d1c2d40ab994e8410c' => [
                        'sku' => 'virtual_product_with_special_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => true,
                        'discounts' => [
                            0 => ['code' => 'special_price', 'price' => 55.55],
                            1 => ['code' => 'catalog_rule', 'price' => 75.08]
                        ],
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
                        'discounts' => [
                            0 => ['code' => 'group', 'price' => 15.15]
                        ],
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
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
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
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 55.55]],
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
