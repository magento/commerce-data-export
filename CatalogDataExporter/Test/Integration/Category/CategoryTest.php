<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\CatalogDataExporter\Model\Provider\Category\AncestorStatusProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Indexer\Cron\UpdateMview;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for category feed
 */
class CategoryTest extends AbstractCategoryTestCase
{
    /**
     * @var UpdateMview
     */
    private $mViewCron;

    protected function setUp(): void
    {
        $this->mViewCron = Bootstrap::getObjectManager()->create(UpdateMview::class);

        parent::setUp();
    }

    /**
     * Validate categories feed data
     *
     * default store view:
     * saas-category (id=100)                           [active, in menu]
     * └── saas-category-sub (id=200)                   [active, not in menu]
     *     └── saas-category-sub-sub (id=300)           [active, in menu]
     *
     * fixture_second_store:
     * saas-category (id=100)                           [active, not in menu]
     * └── saas-category-sub (id=200)                   [not active, not in menu (overridden by ancestor)]
     *     └── saas-category-sub-sub (id=300)           [active, not in menu (overridden by ancestor)]
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_stores.php
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_categories.php
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCategoriesFeedData(): void
    {
        $expectedCategories = [
            [
                'categoryId' => 100,
                'storeViewCode' => 'default',
                'storeCode' => 'main_website_store',
                'websiteCode' => 'base',
                'name' => 'SaaS Category',
                'description' => 'category description',
                'metaTitle' => 'Meta title',
                'metaKeywords' => 'Meta keywords',
                'metaDescription' => 'Meta description',
                'urlKey' => 'saas-category',
                'urlPath' => 'saas-category',
                'image' => 'http://localhost/media/catalog/category/image.jpg',
                'level' => 2,
                'path' => '1/2/100',
                'parentId' => 2,
                'children' => [200],
                'position' => 1,
                'defaultSortBy' => 'name',
                'availableSortBy' => ['name', 'price'],
                'isAnchor' => 1,
                'includeInMenu' => 1,
                'isActive' => 1,
            ],
            [
                'categoryId' => 200,
                'storeViewCode' => 'default',
                'storeCode' => 'main_website_store',
                'websiteCode' => 'base',
                'name' => 'SaaS Category Sub',
                'urlKey' => 'saas-category-sub',
                'urlPath' => 'saas-category/saas-category-sub',
                'image' => '',
                'level' => 3,
                'path' => '1/2/100/200',
                'parentId' => 100,
                'children' => [300],
                'position' => 1,
                'defaultSortBy' => 'name',
                'availableSortBy' => ['name', 'price'],
                'isAnchor' => 1,
                'includeInMenu' => 0,
                'isActive' => 1,
            ],
            [
                'categoryId' => 300,
                'storeViewCode' => 'default',
                'storeCode' => 'main_website_store',
                'websiteCode' => 'base',
                'name' => 'SaaS Category Sub - Sub',
                'urlKey' => 'saas-category-sub-sub',
                'urlPath' => 'saas-category/saas-category-sub/saas-category-sub-sub',
                'image' => '',
                'level' => 4,
                'path' => '1/2/100/200/300',
                'parentId' => 200,
                'children' => null,
                'position' => 1,
                'defaultSortBy' => 'name',
                'availableSortBy' => ['name', 'price'],
                'isAnchor' => 1,
                'includeInMenu' => 1,
                'isActive' => 1,
            ],
            [
                'categoryId' => 100,
                'storeViewCode' => 'fixture_second_store',
                'storeCode' => 'main_website_store',
                'websiteCode' => 'base',
                'name' => 'SaaS Category',
                'description' => 'category description',
                'metaTitle' => 'Meta title',
                'metaKeywords' => 'Meta keywords',
                'metaDescription' => 'Meta description',
                'urlKey' => 'saas-category',
                'urlPath' => 'saas-category',
                'image' => 'http://localhost/media/catalog/category/image.jpg',
                'level' => 2,
                'path' => '1/2/100',
                'parentId' => 2,
                'children' => [200],
                'position' => 1,
                'defaultSortBy' => 'name',
                'availableSortBy' => ['name', 'price'],
                'isAnchor' => 1,
                'includeInMenu' => 0,
                'isActive' => 1,
            ],
            [
                'categoryId' => 200,
                'storeViewCode' => 'fixture_second_store',
                'storeCode' => 'main_website_store',
                'websiteCode' => 'base',
                'name' => 'SaaS Category Sub',
                'urlKey' => 'saas-category-sub',
                'urlPath' => 'saas-category/saas-category-sub',
                'image' => '',
                'level' => 3,
                'path' => '1/2/100/200',
                'parentId' => 100,
                'children' => [300],
                'position' => 1,
                'defaultSortBy' => 'name',
                'availableSortBy' => ['name', 'price'],
                'isAnchor' => 1,
                'includeInMenu' => 0,
                'isActive' => 0,
            ],
            [
                'categoryId' => 300,
                'storeViewCode' => 'fixture_second_store',
                'storeCode' => 'main_website_store',
                'websiteCode' => 'base',
                'name' => 'SaaS Category Sub - Sub',
                'urlKey' => 'saas-category-sub-sub',
                'urlPath' => 'saas-category/saas-category-sub/saas-category-sub-sub',
                'image' => '',
                'level' => 4,
                'path' => '1/2/100/200/300',
                'parentId' => 200,
                'children' => null,
                'position' => 1,
                'defaultSortBy' => 'name',
                'availableSortBy' => ['name', 'price'],
                'isAnchor' => 1,
                'includeInMenu' => 0,
                'isActive' => 1,
            ],
        ];

        foreach ($expectedCategories as $expected) {
            $actual = $this->getCategoryById($expected['categoryId'], $expected['storeViewCode']);
            $this->assertNotEmpty(
                $actual,
                \sprintf('Category %d not found for store %s', $expected['categoryId'], $expected['storeViewCode'])
            );
            foreach ($expected as $field => $value) {
                $this->assertEquals(
                    $value,
                    $actual[$field] ?? null,
                    \sprintf(
                        'Field "%s" mismatch for category %d / store %s',
                        $field,
                        $expected['categoryId'],
                        $expected['storeViewCode']
                    )
                );
            }
        }
    }

    /**
     * Verify children are not active when top level category (id=100, path "1/2/100") is not active,
     * and recover their original values once the top level category is re-activated.
     *
     * Fixture tree:
     *   100 (1/2/100)              default: isActive=true, includeInMenu=true
     *   └── 200 (1/2/100/200)     default: isActive=true, includeInMenu=false
     *       └── 300 (1/2/100/200/300) default: isActive=true, includeInMenu=true
     *
     * Phase 1 - disable: ancestor propagation must force isActive=false on all descendants (200, 300).
     * Phase 2 - re-enable: after the top-level is re-activated the plugin re-schedules descendants,
     * and their original isActive values must be restored.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_stores.php
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_categories.php
     */
    public function testChildrenNotActiveWhenTopLevelNotActive(): void
    {
        /** @var CategoryRepositoryInterface $categoryRepository */
        $categoryRepository = Bootstrap::getObjectManager()->create(CategoryRepositoryInterface::class);
        $rootCategory = $categoryRepository->get(100);

        try {
            // --- Phase 1: deactivate root, verify all descendants become inactive ---
            $rootCategory->setIsActive(false);
            $categoryRepository->save($rootCategory);
            $this->resetAncestorCache();
            $this->mViewCron->execute();

            foreach ([100, 200, 300] as $categoryId) {
                $actual = $this->getCategoryById($categoryId, 'default');
                $this->assertNotEmpty(
                    $actual,
                    \sprintf('Category %d not found for store "default"', $categoryId)
                );
                $this->assertFalse(
                    (bool)$actual['isActive'],
                    \sprintf(
                        'Category %d / store "default": expected isActive=false when ancestor 100 is inactive',
                        $categoryId,
                    )
                );
            }

            // --- Phase 2: re-activate root, verify descendants recover original values ---
            $rootCategory->setIsActive(true);
            $categoryRepository->save($rootCategory);
            $this->resetAncestorCache();
            $this->mViewCron->execute();

            foreach ([100, 200, 300] as $categoryId) {
                $actual = $this->getCategoryById($categoryId, 'default');
                $this->assertNotEmpty(
                    $actual,
                    \sprintf('Category %d not found for store "default" after re-activation', $categoryId)
                );
                $this->assertTrue(
                    (bool)$actual['isActive'],
                    \sprintf(
                        'Category %d / store "default": expected isActive=true after ancestor 100 re-activated',
                        $categoryId,
                    )
                );
            }
        } finally {
            // Ensure root is left active regardless of which phase the test fails in
            $rootCategory->setIsActive(true);
            $categoryRepository->save($rootCategory);
        }
    }

    /**
     * Verify that the parent category's "children" field is updated when a new child is added
     * and the scheduled (mview) indexer runs.
     *
     * ReindexCategoryFeedOnSave::aroundSave detects a new category and adds all ancestor IDs to
     * the mview changelog. When the mview cron runs, the parent is re-indexed and its "children"
     * list reflects the new child.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_stores.php
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_categories.php
     */
    public function testParentChildrenFieldUpdatedWhenChildAddedInScheduledMode(): void
    {
        // Pre-condition: full reindex ran in setUp; category 100 has exactly one child (200).
        $parent = $this->getCategoryById(100, 'default');
        $this->assertNotEmpty($parent, 'Pre-condition: category 100 must exist in feed');
        $this->assertEquals([200], $parent['children'], 'Pre-condition: category 100 should have children [200]');

        $newChildId = 450;

        /** @var Category $newChild */
        $newChild = Bootstrap::getObjectManager()->create(Category::class);
        try {
            // ensure items from changelog are processed
            $this->mViewCron->execute();
            // Create a new direct child under category 100 (simulates a merchant adding a subcategory).
            // Explicit ID+path are required: Magento only auto-computes path when a parent path is
            // already stored, which isn't the case for brand-new unsaved models.
            $newChild->isObjectNew(true);
            $newChild
                ->setId($newChildId)
                ->setName('New Child Category')
                ->setParentId(100)
                ->setPath('1/2/100/' . $newChildId)
                ->setUrlKey('new-child-category-450')
                ->setLevel(3)
                ->setAvailableSortBy(['name', 'price'])
                ->setDefaultSortBy('name')
                ->setIsActive(true)
                ->setIncludeInMenu(true)
                ->setPosition(2)
                ->setStoreId(0)
                ->save();

            $this->mViewCron->execute();

            // The parent feed entry must reflect the new child in its "children" list.
            $updatedParent = $this->getCategoryById(100, 'default');
            $this->assertNotEmpty($updatedParent, 'Category 100 must exist in feed after child added');
            $this->assertContains(
                (string)$newChildId,
                $updatedParent['children'] ?? [],
                'Category 100 children should include newly added child ' . $newChildId
            );
        } finally {
            // Direct DB deletion avoids Staging EE area restriction on ORM delete.
            // Must delete in dependency order: url_rewrite and sequence have no FK cascade.
            $this->connection->delete(
                $this->resource->getTableName('catalog_category_entity'),
                ['entity_id = ?' => $newChildId]
            );
            $this->connection->delete(
                $this->resource->getTableName('url_rewrite'),
                ['entity_type = ?' => 'category', 'entity_id = ?' => $newChildId]
            );
            if ($this->connection->isTableExists($this->resource->getTableName('sequence_catalog_category'))) {
                $this->connection->delete(
                    $this->resource->getTableName('sequence_catalog_category'),
                    ['sequence_value = ?' => $newChildId]
                );
            }
        }
    }

    /**
     * Resets AncestorStatusProvider's in-memory cache between indexer runs within the same test.
     */
    private function resetAncestorCache(): void
    {
        $provider = Bootstrap::getObjectManager()->get(AncestorStatusProvider::class);
        $ref = new \ReflectionClass($provider);
        $prop = $ref->getProperty('cache');
        $prop->setValue($provider, []);
    }
}
