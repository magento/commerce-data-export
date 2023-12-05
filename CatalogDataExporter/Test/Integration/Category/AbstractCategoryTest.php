<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Indexer\Model\Indexer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Abstract class for category feed tests
 */
abstract class AbstractCategoryTest extends TestCase
{
    /**
     * Category feed indexer id
     */
    private const CATEGORY_FEED_INDEXER = 'catalog_data_exporter_categories';

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FeedInterface
     */
    protected $categoryFeed;

    /**
     * @inheritDoc
     */
    protected function setUp() : void
    {
        $this->resource = Bootstrap::getObjectManager()->create(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $this->jsonSerializer = Bootstrap::getObjectManager()->create(Json::class);
        $this->categoryRepository = Bootstrap::getObjectManager()->create(CategoryRepositoryInterface::class);
        $this->storeManager = Bootstrap::getObjectManager()->create(StoreManagerInterface::class);
        $this->categoryFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('categories');

        $this->indexer->load(self::CATEGORY_FEED_INDEXER);
        $this->reindexCategoryDataExporterTable();
    }

    /**
     * Assert base category data
     *
     * @param CategoryInterface $category
     * @param array $extract
     * @param StoreInterface $store
     */
    protected function assertBaseCategoryData(CategoryInterface $category, array $extract, StoreInterface $store) : void
    {
        $this->assertNotEmpty($extract, "Exported Category Data is empty");

        $storeCode = $this->storeManager->getGroup($store->getStoreGroupId())->getCode();
        $websiteCode = $this->storeManager->getWebsite($store->getWebsiteId())->getCode();
        $this->assertEquals($store->getCode(), $extract['storeViewCode']);
        $this->assertEquals($storeCode, $extract['storeCode']);
        $this->assertEquals($websiteCode, $extract['websiteCode']);
        $this->assertEquals($category->getId(), $extract['categoryId']);
        $this->assertEquals($category->getIsActive(), $extract['isActive']);
        $this->assertEquals($category->getName(), $extract['name']);
        $this->assertEquals($category->getPath(), $extract['path']);
        $this->assertEquals($category->getUrlKey(), $extract['urlKey']);
        $this->assertEquals($category->getUrlPath(), $extract['urlPath']);
        $this->assertEquals($category->getPosition(), $extract['position']);
        $this->assertEquals($category->getLevel(), $extract['level']);
        $this->assertEquals($category->getParentId(), $extract['parentId']);
        $this->assertEquals($category->getCreatedAt(), $extract['createdAt']);
        $this->assertEquals($category->getUpdatedAt(), $extract['updatedAt']);
        $this->assertEquals($category->getDefaultSortBy(), $extract['defaultSortBy']);
        $this->assertEquals($category->getImageUrl(), $extract['image']);
    }

    /**
     * @param int $categoryId
     * @param string $storeViewCode
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getCategoryById(int $categoryId, string $storeViewCode) : array
    {
        foreach ($this->categoryFeed->getFeedSince('1')['feed'] as $item) {
            if ($item['categoryId'] == $categoryId && $item['storeViewCode'] === $storeViewCode) {
                return $item;
            }
        }
        return [];
    }

    /**
     * Run the category exporter indexer
     *
     * @param array $ids
     * @return void
     */
    protected function emulatePartialReindexBehavior(array $ids = []) : void
    {
        $this->indexer->reindexList($ids);
    }

    /**
     * Reindex the full category data exporter table
     *
     * @return void
     */
    private function reindexCategoryDataExporterTable() : void
    {
        $this->indexer->reindexAll();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->truncateCategoryDataExporterIndexTable();
    }

    /**
     * Truncates index table
     */
    private function truncateCategoryDataExporterIndexTable(): void
    {
        $connection = $this->resource->getConnection();
        $feedTable = $this->resource->getTableName(self::CATEGORY_FEED_INDEXER);
        $connection->truncateTable($feedTable);
    }
}
