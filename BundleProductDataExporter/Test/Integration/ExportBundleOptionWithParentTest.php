<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleProductDataExporter\Test\Integration;

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
 * Check parents fields for options bundle products
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportBundleOptionWithParentTest extends TestCase
{
    private const PRODUCT_FEED_INDEXER = 'catalog_data_exporter_products';

    /**
     * @var Indexer
     */
    private Indexer $indexer;

    /**
     * @var FeedInterface
     */
    private FeedInterface $productsFeed;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ResourceConnection
     */
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
        $this->productsFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('products');
        $this->resourceConnection = Bootstrap::getObjectManager()->create(ResourceConnection::class);
    }

    /**
     * @magentoDataFixture Magento/Bundle/_files/product_with_multiple_options.php
     * @magentoDataFixture Magento/Bundle/_files/bundle_product_with_dynamic_price.php
     *
     * @dataProvider expectedBundleOptionsWithParentData
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportBundleOptionsWithParentData(array $expectedSimpleProduct): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProduct);
    }

    /**
     * @return \array[][]
     */
    private function expectedBundleOptionsWithParentData(): array
    {
        return [
            [
                [
                    [
                        'sku' => 'simple1',
                        'type' => 'SIMPLE',
                        'parents' => [
                            0 => ['sku' => 'bundle-product', 'productType' => 'bundle_fixed'],
                            1 => ['sku' => 'bundle_product_with_dynamic_price', 'productType' => 'bundle'],
                        ],
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
        $actualProductsFeed = $this->productsFeed->getFeedSince($timestamp->format(DateTimeInterface::W3C));

        self::assertNotEmpty($actualProductsFeed['feed'], 'Product Feed should not be empty');

        foreach ($expectedItems as $index => $product) {
            if (!isset($actualProductsFeed['feed'][$index])) {
                self::fail("Cannot find product feed");
            }

            self::assertEquals(
                $product['sku'],
                $actualProductsFeed['feed'][$index]['sku'],
                "Sku is not equal for index {$index}"
            );

            self::assertEqualsCanonicalizing(
                $product['parents'],
                $actualProductsFeed['feed'][$index]['parents'],
                "Parents is not equal"
            );
        }
    }

    /**
     * Run the indexer to extract product data
     * @param $ids
     * @return void
     */
    private function runIndexer($ids): void
    {
        try {
            $this->indexer->load(self::PRODUCT_FEED_INDEXER);
            $this->indexer->reindexList($ids);
        } catch (Throwable) {
            throw new RuntimeException('Could not reindex product data');
        }
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
        $feedTable = $this->resourceConnection->getTableName('catalog_data_exporter_products');
        $connection->truncateTable($feedTable);
    }
}
