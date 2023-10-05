<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ParentProductDataExporter\Test\Integration;

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
 * Check parents fields for all types of relation products
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportParentProductTest extends TestCase
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
     * @magentoDataFixture Magento_ParentProductDataExporter::Test/_files/configurable_products.php
     * @dataProvider expectedSimpleConfigurableWithParentsData
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportSimpleConfigurableProductsWithParentData(array $expectedSimpleProduct): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProduct);
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleConfigurableWithParentsData(): array
    {
        return [
            [
                [
                    [
                        'sku' => 'configurable-data-parent-test-child-1',
                        'type' => 'SIMPLE',
                        'parents' => [
                            0 => ['sku' => 'configurable-data-parent-test', 'productType' => 'configurable'],
                        ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @magentoDataFixture Magento_ParentProductDataExporter::Test/_files/grouped_products.php
     * @dataProvider expectedSimpleProductWithParentsData
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function testExportSimpleProductsWithParentData(array $expectedSimpleProduct): void
    {
        $this->checkExpectedItemsAreExportedInFeed($expectedSimpleProduct);
    }

    /**
     * @return \array[][]
     */
    private function expectedSimpleProductWithParentsData(): array
    {
        return [
            [
                [
                    [
                        'sku' => 'simple_product_parent_product_test_1',
                        'type' => 'SIMPLE',
                        'parents' => [
                            2 => ['sku' => 'grouped-product-parent-product-test', 'productType' => 'grouped']
                        ],
                    ],
                    [
                        'sku' => 'simple_product_parent_product_test_2',
                        'type' => 'SIMPLE',
                        'parents' => [
                            1 => ['sku' => 'grouped-product-parent-product-test', 'productType' => 'grouped']
                        ],
                    ],
                    [
                        'sku' => 'simple_product_with_no_parent_test',
                        'type' => 'SIMPLE',
                        'parents' => null,
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

        foreach ($expectedItems as $expectedProduct) {
            $expectedFeedFound = false;
            foreach ($actualProductsFeed['feed'] as $productFeed) {
                if ($productFeed['sku'] === $expectedProduct['sku']) {
                    $expectedFeedFound = true;
                    self::assertEqualsCanonicalizing(
                        $expectedProduct['parents'],
                        $productFeed['parents'],
                        "Parents is not equal"
                    );
                }
            }
            if (false === $expectedFeedFound) {
                self::fail("Cannot find product price feed");
            }
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
