<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\DataExporter\Model\FeedHashBuilder;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SaaSCatalog\Cron\ProductSubmitFeed;
use Magento\SaaSCommon\Cron\SubmitFeedInterface;
use Magento\SaaSCommon\Test\Integration\ExportFeedStub;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class to check that only feeds with "resyncable" statuses would be re-submitted
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResubmitFailedFeedTest extends AbstractProductTestHelper
{
    private const EXPORT_SUCCESS_STATUS = 200;

    /**
     * @var FeedInterface
     */
    private FeedInterface $productFeed;

    /**
     * @var SubmitFeedInterface|ProductSubmitFeed|mixed
     */
    private SubmitFeedInterface $submitFeed;

    /**
     * @var ResourceConnection|mixed
     */
    private ResourceConnection $resourceConnection;

    private FeedHashBuilder $hashBuilder;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Bootstrap::getObjectManager()->configure([
            'preferences' => [
                ExportFeedInterface::class =>
                    ExportFeedStub::class,
            ]
        ]);
        $connection = Bootstrap::getObjectManager()->create(ResourceConnection::class)->getConnection();
        $feedTable = $connection->getTableName(
            Bootstrap::getObjectManager()->get(FeedPool::class)
                ->getFeed('products')
                ->getFeedMetadata()
                ->getFeedTableName()
        );
        $connection->truncateTable($feedTable);
    }

    /**
     * Integration test setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->productFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('products');
        $this->submitFeed = Bootstrap::getObjectManager()->get(ProductSubmitFeed::class); // @phpstan-ignore-line
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->resourceConnection = Bootstrap::getObjectManager()->create(ResourceConnection::class);
        $this->hashBuilder = Bootstrap::getObjectManager()->create(FeedHashBuilder::class);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture services_connector/services_connector_integration/production_api_key test_key
     * @magentoConfigFixture services_connector/services_connector_integration/production_private_key private_test_key
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     * @dataProvider productsWithStatusesDataProvider
     *
     * @param array $expectedProducts
     * @return void
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function testResubmitFailedFeed(array $expectedProducts) : void
    {
        $this->updateFeeds($expectedProducts);
        $this->submitFeed->execute();

        $feeds = $this->productFeed->getFeedSince('1');
        foreach ($expectedProducts as $expectedProduct) {
            $this->checkProductInFeed($expectedProduct, $feeds['feed']);
        }
    }

    /**
     * @param array $expectedProducts
     * @return void
     * @throws NoSuchEntityException
     */
    private function updateFeeds(array $expectedProducts): void
    {
        $queryData = [];
        $metadata = $this->productFeed->getFeedMetadata();
        foreach ($expectedProducts as $productData) {
            $productId = $this->productRepository->get($productData['sku'])->getId();
            $identifierMapData = [
                'storeViewCode' => $productData['store_view_code'],
                'sku' => $productData['sku']
            ];
            $queryData[] = [
                'source_entity_id' => $productId,
                'feed_id' => $this->hashBuilder->buildIdentifierFromFeedItem($identifierMapData, $metadata),
                'status' => $productData['status']
            ];
        }

        $connection = $this->resourceConnection->getConnection();
        $connection->insertOnDuplicate(
            $connection->getTableName($metadata->getFeedTableName()),
            $queryData
        );
    }

    /**
     * @param array $expectedProduct
     * @param array $actualFeed
     * @return void
     */
    private function checkProductInFeed(array $expectedProduct, array $actualFeed): void
    {
        $productStatusCorrect = $expectedProduct['expected_status'] !== self::EXPORT_SUCCESS_STATUS;
        foreach ($actualFeed as $actualProductData) {
            if (!$productStatusCorrect
                && $expectedProduct['sku'] === $actualProductData['sku']
                && $expectedProduct['store_view_code'] === $actualProductData['storeViewCode']) {
                $productStatusCorrect = true;
                break;
            }
        }

        self::assertTrue($productStatusCorrect, 'Product ' . $expectedProduct['sku']
            . 'has wrong status or absent in the feed');
    }

    /**
     * Get product with statuses
     *
     * @return array[]
     */
    public static function productsWithStatusesDataProvider(): array
    {
        return [
            [
                [
                    [
                        'sku' => 'simple1',
                        'store_view_code' => 'default',
                        'status' => self::EXPORT_SUCCESS_STATUS,
                        'expected_status' => self::EXPORT_SUCCESS_STATUS
                    ],
                    [
                        'sku' => 'simple1',
                        'store_view_code' => 'fixture_second_store',
                        'status' => ExportStatusCodeProvider::APPLICATION_ERROR,
                        'expected_status' => self::EXPORT_SUCCESS_STATUS
                    ],
                    [
                        'sku' => 'simple2',
                        'store_view_code' => 'default',
                        'status' => self::EXPORT_SUCCESS_STATUS,
                        'expected_status' => self::EXPORT_SUCCESS_STATUS
                    ],
                    [
                        'sku' => 'simple2',
                        'store_view_code' => 'fixture_second_store',
                        'status' => 400,
                        'expected_status' => 400
                    ],
                    [
                        'sku' => 'simple3',
                        'store_view_code' => 'default',
                        'status' => ExportStatusCodeProvider::APPLICATION_ERROR,
                        'expected_status' => self::EXPORT_SUCCESS_STATUS
                    ],
                    [
                        'sku' => 'simple3',
                        'store_view_code' => 'fixture_second_store',
                        'status' => 500,
                        'expected_status' => self::EXPORT_SUCCESS_STATUS
                    ]
                ]
            ]
        ];
    }
}
