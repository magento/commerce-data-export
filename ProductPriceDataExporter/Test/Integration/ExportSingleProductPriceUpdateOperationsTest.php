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
use Magento\Framework\ObjectManagerInterface;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\Processor;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use Zend_Db_Statement_Exception;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Check prices for single (non-complex) products with update operations
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class ExportSingleProductPriceUpdateOperationsTest extends TestCase
{
    private const PRODUCT_PRICE_FEED_INDEXER = 'catalog_data_exporter_product_prices';

    private Indexer $indexer;

    private FeedInterface $productPricesFeed;

    private ProductRepositoryInterface $productRepository;

    private ResourceConnection $resourceConnection;

    private ObjectManagerInterface $objectManager;

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
        $this->objectManager = Bootstrap::getObjectManager();
        $this->indexer = $this->objectManager->create(Indexer::class);
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $this->productPricesFeed = $this->objectManager->get(FeedPool::class)->getFeed('productPrices');
        $this->resourceConnection = $this->objectManager->create(ResourceConnection::class);
    }

    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/catalog_data_exporter_product_prices_indexer_update_on_schedule.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_all_websites_grouped_price.php
     * @dataProvider expectedSimpleProductPricesUnassignedWebsiteDataProvider
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testUnassignProductFromWebsite(array $expectedSimpleProductPrices): void
    {
        $expectedIds = [];
        foreach ($expectedSimpleProductPrices as $expectedItem) {
            $expectedIds[] = $this->productRepository->get($expectedItem['sku'])->getId();
        }
        $this->runIndexer($expectedIds);
        $product = $this->productRepository->get('simple_product_with_tier_price');
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $secondWebsiteId = $websiteRepository->get('test')->getId();
        $product->setWebsiteIds([$secondWebsiteId]);
        $this->productRepository->save($product);
        self::markTestSkipped("System uses DELETE logic instead of UPDATE. Should be changed in MDEE-442");
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices, $expectedIds);
    }

    /**
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/configure_website_scope_price.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/catalog_data_exporter_product_prices_indexer_update_on_schedule.php
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/simple_products_all_websites_grouped_price.php
     * @dataProvider expectedSimpleProductPricesReassignProductsToWebsiteDataProvider
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testReassignProductToWebsite(array $expectedSimpleProductPrices): void
    {
        $expectedIds = [];
        foreach ($expectedSimpleProductPrices as $expectedItem) {
            $expectedIds[] = $this->productRepository->get($expectedItem['sku'])->getId();
        }
        $this->runIndexer($expectedIds);
        $product = $this->productRepository->get('simple_product_with_tier_price');
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $firstWebsiteId = $websiteRepository->get('base')->getId();
        $secondWebsiteId = $websiteRepository->get('test')->getId();
        $product->setWebsiteIds([$secondWebsiteId]);
        $this->productRepository->save($product);
        $this->runIndexer($expectedIds);
        $product->setWebsiteIds([$firstWebsiteId, $secondWebsiteId]);
        $this->productRepository->save($product);

        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProductPrices, $expectedIds);
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
     * @param array $expectedItems
     * @param array $ids
     * @return void
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    private function checkExpectedItemsAreExportedInFeed(array $expectedItems, array $ids): void
    {
        $timestamp = new DateTime('Now - 1 second');
        $processor = $this->objectManager->create(Processor::class);
        $processor->updateMview();
        $processor->reindexAllInvalid();
        $actualProductPricesFeed = $this->productPricesFeed->getFeedSince($timestamp->format(DateTimeInterface::W3C));
        self::assertNotEmpty($actualProductPricesFeed['feed'], 'Product Price Feed should not be empty');
        $feedsToCheck = [];
        foreach ($actualProductPricesFeed['feed'] as $product) {
            if (array_contains($ids, (string)$product['productId'])) {
                $itemKey = $product['sku'] . '_' . $product['websiteCode'] . '_' . $product['customerGroupCode'];
                $feedsToCheck[$itemKey] = $this->unsetNotImportantField($product);
            }
        }
        if (!empty($feedsToCheck)) {
            self::assertCount(
                count($ids),
                $feedsToCheck,
                'Product Price Feeds does not contain all expected items'
            );
        } else {
            self::fail("There are no expected products in the feed");
        }

        foreach ($expectedItems as $expectedKey => $expectedItem) {
            if (!isset($feedsToCheck[$expectedKey])) {
                self::fail("Cannot find product price feed");
            }
            self::assertEquals(
                $expectedItem,
                $feedsToCheck[$expectedKey],
                "Some items are missing in product price feed " . $expectedItem['sku']
            );
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
