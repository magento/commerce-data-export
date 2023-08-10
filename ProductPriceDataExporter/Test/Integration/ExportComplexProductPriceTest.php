<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Test\Integration;

use DateTime;
use DateTimeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use Zend_Db_Statement_Exception;

/**
 * Check prices for complex products
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class ExportComplexProductPriceTest extends TestCase
{
    private const PRODUCT_PRICE_FEED_INDEXER = 'catalog_data_exporter_product_prices';

    private Indexer $indexer;

    private FeedInterface $productPricesFeed;

    private ProductRepositoryInterface $productRepository;

    private ResourceConnection $resourceConnection;

    /**
     * @param string|null $name
     * @param array $data
     * @param $dataName
     */
    public function __construct(
        ?string $name = null,
        array $data = [],
        $dataName = ''
    ) {
        parent::__construct($name, $data, $dataName);
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->productPricesFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('prices');
        $this->resourceConnection = Bootstrap::getObjectManager()->create(ResourceConnection::class);
    }

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
                    [
                        'sku' => 'bundle_fixed_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 105.1,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'BUNDLE_FIXED'
                    ],
                    [
                        'sku' => 'bundle_fixed_product_with_regular_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 105.1,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'BUNDLE_FIXED'
                    ],
                    [
                        'sku' => 'bundle_fixed_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 155.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'percentage' => 55.55]],
                        'type' => 'BUNDLE_FIXED'
                    ],
                    [
                        'sku' => 'bundle_fixed_product_with_special_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 155.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'percentage' => 55.55]],
                        'type' => 'BUNDLE_FIXED'
                    ],
                    [
                        'sku' => 'bundle_fixed_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'BUNDLE_FIXED'
                    ],
                    [
                        'sku' => 'bundle_fixed_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'BUNDLE_FIXED'
                    ],
                    [
                        'sku' => 'bundle_fixed_product_with_tier_price',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 10]],
                        'type' => 'BUNDLE_FIXED'
                    ],
                    [
                        'sku' => 'bundle_fixed_product_with_tier_price',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 100.1,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'percentage' => 10]],
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
                    [
                        'sku' => 'simple_option_1',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_regular_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_1',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_regular_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_2',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_regular_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_2',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_regular_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_3',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 10.1]],
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_special_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_3',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 10.1]],
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_special_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_4',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 10.1]],
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_special_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_4',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 10.1]],
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_special_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_5',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 20.20,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_tier_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_5',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 20.20,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_tier_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_5',
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 20.20,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_tier_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_5',
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 20.20,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'parents' => [
                            [
                                'type' => 'BUNDLE',
                                'sku' => 'bundle_dynamic_product_with_tier_price'
                            ]
                        ],
                        'type' => 'SIMPLE'
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
                    [
                        'sku' => 'simple_option_1',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 155.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_1',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 155.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 150,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 150,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 150,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 150,
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
     */
    private function expectedConfigurableRegularProductPricesDataProvider(): array
    {
        return [
            [
                [
                    [
                        'sku' => 'simple_option_1',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_1',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 105.1,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple_option_2',
                        'parents' => [0 => ['sku' => 'configurable', 'type' => 'CONFIGURABLE']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 105.1,
                        'deleted' => false,
                        'discounts' => null,
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
                    [
                        'sku' => 'simple',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 55.55,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 155.15,
                        'deleted' => false,
                        'discounts' => null,
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 155.15,
                        'deleted' => false,
                        'discounts' => null,
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
                    [
                        'sku' => 'simple',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 155.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'simple',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 155.15,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'special_price', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'base',
                        'regular' => 10,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 16.16]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'base',
                        'regular' => 10,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 15.15]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => '0',
                        'websiteCode' => 'test',
                        'regular' => 10,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 14.14]],
                        'type' => 'SIMPLE'
                    ],
                    [
                        'sku' => 'virtual-product',
                        'parents' => [0 => ['sku' => 'grouped-product', 'type' => 'GROUPED']],
                        'customerGroupCode' => 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c',
                        'websiteCode' => 'test',
                        'regular' => 10,
                        'deleted' => false,
                        'discounts' => [0 => ['code' => 'group', 'price' => 13.13]],
                        'type' => 'SIMPLE'
                    ],
                ]
            ]
        ];
    }

    /**
     * @param array $expectedItems
     * @return void
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    private function checkExpectedItemsAreExportedInFeed(array $expectedItems): void
    {
        $ids = [];
        foreach ($expectedItems as $expectedItem) {
            $ids[] = $this->productRepository->get($expectedItem['sku'])->getId();
        }
        $timestamp = new DateTime('Now - 1 second');
        $this->runIndexer($ids);
        $actualProductPricesFeed = $this->productPricesFeed->getFeedSince($timestamp->format(DateTimeInterface::W3C));
        self::assertNotEmpty($actualProductPricesFeed['feed'], 'Product Price Feed should not be empty');
        self::assertCount(
            count($expectedItems),
            $actualProductPricesFeed['feed'],
            'Product Price Feeds does not contain all expected items'
        );

        foreach ($expectedItems as $index => $product) {
            if (!isset($actualProductPricesFeed['feed'][$index])) {
                self::fail("Cannot find product price feed");
            }

            // unset fields from feed that we don't care about for test
            $actualFeed = $this->unsetNotImportantField($actualProductPricesFeed['feed'][$index]);
            self::assertEquals($product, $actualFeed, "Some items are missing in product price feed $index");
        }
    }

    /**
     * Run the indexer to extract product prices data
     * @param $ids
     * @return void
     */
    private function runIndexer($ids): void
    {
        try {
            $this->indexer->load(self::PRODUCT_PRICE_FEED_INDEXER);
            $this->indexer->reindexList($ids);
        } catch (Throwable) {
            throw new RuntimeException('Could not reindex product prices data');
        }
    }

    /**
     * @param array $actualProductPricesFeed
     * @return array
     */
    private function unsetNotImportantField(array $actualProductPricesFeed): array
    {
        $actualFeed = $actualProductPricesFeed;

        unset(
            $actualFeed['modifiedAt'],
            $actualFeed['updatedAt'],
            $actualFeed['productId'],
            $actualFeed['websiteId']
        );

        return $actualFeed;
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->truncateIndexTable();
    }

    /**
     * Truncates index table
     */
    private function truncateIndexTable(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $feedTable = $this->resourceConnection->getTableName('catalog_data_exporter_product_prices');
        $connection->truncateTable($feedTable);
    }
}
