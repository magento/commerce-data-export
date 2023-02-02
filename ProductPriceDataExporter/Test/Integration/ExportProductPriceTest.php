<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Indexer\Model\Indexer;
use Magento\DataExporter\Export\Processor;
use Magento\ProductPriceDataExporter\Model\Provider\ProductPrice;
use Magento\TestFramework\Helper\Bootstrap;
use RuntimeException;
use Throwable;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class ExportProductPriceTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_PRICE_FEED_INDEXER = 'catalog_data_exporter_product_prices';

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var FeedInterface
     */
    protected $productPricesFeed;

    /**
     * @var ProductRepositoryInterface|mixed
     */
    private $productRepository;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $this->processor = Bootstrap::getObjectManager()->create(Processor::class);
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->productPricesFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('productPrices');
    }

    /**
     * @magentoDataFixture Magento_ProductPriceDataExporter::Test/_files/products.php
     */
    public function testExportProductPricesForSimpleProducts()
    {
        $productPricesForSimpleProducts = $this->getExpectedProductPricesForSimpleProducts();

        $this->checkExpectedItemsAreExportedInFeed($productPricesForSimpleProducts);
    }

    /**
     * @return \array[][]
     */
    private function getExpectedProductPricesForSimpleProducts(): array
    {
        return [
            [
                'sku' => 'simple_product_with_regular_price',
                'customerGroupCode' => '0',
                'regular' => 10,
                'deleted' => false,
                'discounts' => null,
            ],
            [
                'sku' => 'simple_product_with_special_price',
                'customerGroupCode' => '0',
                'regular' => 20,
                'deleted' => false,
                'discounts' => [0 => ['code' => 'special_price', 'price' => 5]],
            ],
            [
                'sku' => 'virtual_product_with_special_price',
                'customerGroupCode' => '0',
                'regular' => 200,
                'deleted' => false,
                'discounts' => [0 => ['code' => 'special_price', 'price' => 50]],
            ],
            [
                'sku' => 'grouped_product',
                'customerGroupCode' => '0',
                'regular' => 0,
                'deleted' => false,
                'discounts' => null,
            ],
            [
                'sku' => 'simple_product_with_special_price_for_cg',
                'customerGroupCode' => '0',
                'regular' => 30,
                'deleted' => false,
                'discounts' => null,
            ],
            [
                'sku' => 'simple_product_with_special_price_for_cg',
                'customerGroupCode' => '356a192b7913b04c54574d18c28d46e6395428ab',
                'regular' => 30,
                'deleted' => false,
                'discounts' => [0 => ['code' => 'group', 'price' => 15]],
            ],
        ];
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @depends testExportProductPricesForSimpleProducts
     */
    public function testExportProductPricesForConfigurableProducts()
    {
        $productPricesForConfigurableProducts = $this->getExpectedProductPricesForConfigurableProducts();

        $this->checkExpectedItemsAreExportedInFeed($productPricesForConfigurableProducts);
    }

    /**
     * @return \array[][]
     */
    private function getExpectedProductPricesForConfigurableProducts(): array
    {
        return [
            [
                'sku' => 'simple_10',
                'parents' => [0 => ['sku' => 'configurable', 'type' => ProductPrice::PRODUCT_TYPE_CONFIGURABLE]],
                'customerGroupCode' => '0',
                'regular' => 10,
                'deleted' => false,
                'discounts' => null,
            ],
            [
                'sku' => 'simple_20',
                'parents' => [0 => ['sku' => 'configurable', 'type' => ProductPrice::PRODUCT_TYPE_CONFIGURABLE]],
                'customerGroupCode' => '0',
                'regular' => 20,
                'deleted' => false,
                'discounts' => null,
            ],
        ];
    }

    /**
     * @magentoDataFixture Magento/Bundle/_files/product.php
     * @depends testExportProductPricesForConfigurableProducts
     */
    public function testExportProductPricesForBundleProducts()
    {
        $productPricesForBundleProducts = $this->getExpectedProductPricesForBundleProducts();

        $this->checkExpectedItemsAreExportedInFeed($productPricesForBundleProducts);
    }

    /**
     * @return \array[][]
     */
    private function getExpectedProductPricesForBundleProducts(): array
    {
        return [
            [
                'sku' => 'simple',
                'parents' => [0 => ['sku' => 'bundle-product', 'type' => ProductPrice::PRODUCT_TYPE_BUNDLE]],
                'customerGroupCode' => '0',
                'regular' => 10,
                'deleted' => false,
                'discounts' => null,
            ],
            // this sku comes from the fixture inside Magento/Bundle/_files/product.php and is not related to the bundle
            [
                'sku' => 'custom-design-simple-product',
                'customerGroupCode' => '0',
                'regular' => 10,
                'deleted' => false,
                'discounts' => null,
            ],
        ];
    }

    /**
     * @return void
     */
    private function checkExpectedItemsAreExportedInFeed(array $expectedItems): void
    {
        $ids = [];
        foreach ($expectedItems as $expectedItem) {
            $ids[] = $this->productRepository->get($expectedItem['sku'])->getId();
        }
        $timestamp = new \DateTime('Now - 1 second');
        $this->runIndexer($ids);
        $actualProductPricesFeed = $this->productPricesFeed->getFeedSince($timestamp->format(\DateTime::W3C));

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
    private function runIndexer($ids) : void
    {
        try {
            $this->indexer->load(self::PRODUCT_PRICE_FEED_INDEXER);
            $this->indexer->reindexList($ids);
        } catch (Throwable $e) {
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
            $actualFeed['websiteId'],
            $actualFeed['websiteCode']
        );

        return $actualFeed;
    }
}
