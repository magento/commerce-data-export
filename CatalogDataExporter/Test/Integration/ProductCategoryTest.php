<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Framework\Registry;
use Magento\Indexer\Cron\UpdateMview;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Verifies that the product feed's categoryData field correctly reflects category is_active status changes.
 *
 * Fixture tree (categories from setup_categories.php):
 *   100 (1/2/100, level 2)           SaaS Category
 *   └── 200 (1/2/100/200, level 3)   SaaS Category Sub  ← product assigned here
 *
 * @magentoAppArea adminhtml
 */
class ProductCategoryTest extends AbstractProductTestHelper
{
    private const PRODUCT_SKU = 'simple1';
    private const CATEGORY_ID = 200;
    private const STORE_VIEW_CODE = 'default';
    private const EXPECTED_CATEGORY_PATH = 'saas-category/saas-category-sub';

    /**
     * @var UpdateMview
     */
    private UpdateMview $mViewCron;

    protected function setUp(): void
    {
        $this->mViewCron = Bootstrap::getObjectManager()->create(UpdateMview::class);
        parent::setUp();
    }

    /**
     * Verify that disabling a category removes it from the product feed's categoryData,
     * and re-enabling it restores the entry.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     */
    public function testCategoryStatusChangeAffectsProductCategoryData(): void
    {
        $productId = $this->getProductId(self::PRODUCT_SKU);

        // Pre-condition: category 200 is active; product feed must list it in categoryData.
        $this->emulatePartialReindexBehavior([$productId]);
        $feed = $this->getExtractedProduct(self::PRODUCT_SKU, self::STORE_VIEW_CODE);
        $this->assertNotEmpty($feed, 'Pre-condition: product must exist in feed');
        $this->assertCategoryInCategoryData(
            self::CATEGORY_ID,
            $feed['feedData']['categoryData'] ?? [],
            'Pre-condition: category 200 must appear in categoryData when active'
        );
        $this->assertCategoryPath(
            self::CATEGORY_ID,
            self::EXPECTED_CATEGORY_PATH,
            $feed['feedData']['categoryData'] ?? []
        );

        $category = $this->categoryRepository->get(self::CATEGORY_ID);
        try {
            // --- Phase 1: disable category ---
            $category->setIsActive(false);
            $this->categoryRepository->save($category);
            // ResyncProductsOnCategoryChange adds product IDs to the changelog; UpdateMview processes it.
            $this->mViewCron->execute();

            $feed = $this->getExtractedProduct(self::PRODUCT_SKU, self::STORE_VIEW_CODE);
            $this->assertNotEmpty(
                $feed['feedData']['categoryData'] ?? [],
                'Phase 1: product must still exist in feed after category disabled'
            );
            $this->assertCategoryNotInCategoryData(
                self::CATEGORY_ID,
                $feed['feedData']['categoryData'],
                'Phase 1: category 200 must be absent from categoryData when disabled'
            );

            // --- Phase 2: re-enable category ---
            $category->setIsActive(true);
            $this->categoryRepository->save($category);
            $this->mViewCron->execute();

            $feed = $this->getExtractedProduct(self::PRODUCT_SKU, self::STORE_VIEW_CODE);
            $this->assertNotEmpty($feed, 'Phase 2: product must still exist in feed after category re-enabled');
            $this->assertCategoryInCategoryData(
                self::CATEGORY_ID,
                $feed['feedData']['categoryData'] ?? [],
                'Phase 2: category 200 must return to categoryData after re-enabled'
            );
            $this->assertCategoryPath(
                self::CATEGORY_ID,
                self::EXPECTED_CATEGORY_PATH,
                $feed['feedData']['categoryData'] ?? []
            );
        } finally {
            $category->setIsActive(true);
            $this->categoryRepository->save($category);
        }
    }

    /**
     * Verify that deleting a category removes it from the product feed's categoryData
     * after the mview cron processes the resulting product changelog entries.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     */
    public function testCategoryDeletionRemovesProductCategoryData(): void
    {
        $productId = $this->getProductId(self::PRODUCT_SKU);

        // Pre-condition: category 200 is active; product feed must list it in categoryData.
        $this->emulatePartialReindexBehavior([$productId]);
        $feed = $this->getExtractedProduct(self::PRODUCT_SKU, self::STORE_VIEW_CODE);
        $this->assertNotEmpty($feed, 'Pre-condition: product must exist in feed');
        $this->assertCategoryInCategoryData(
            self::CATEGORY_ID,
            $feed['feedData']['categoryData'] ?? [],
            'Pre-condition: category 200 must appear in categoryData before deletion'
        );

        // Delete category 200.
        $registry = Bootstrap::getObjectManager()->get(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);
        $this->categoryRepository->deleteByIdentifier(self::CATEGORY_ID);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);

        // Cron run: UpdateMview processes the changelog and re-indexes affected products.
        $this->emulateCustomersBehaviorAfterDeleteAction();
        $this->mViewCron->execute();

        $feed = $this->getExtractedProduct(self::PRODUCT_SKU, self::STORE_VIEW_CODE);
        $this->assertNotEmpty($feed, 'Product must still exist in feed after category deletion');
        $this->assertCategoryNotInCategoryData(
            self::CATEGORY_ID,
            $feed['feedData']['categoryData'] ?? [],
            'Category 200 must be absent from categoryData after deletion'
        );
    }

    /**
     * @param int $categoryId
     * @param array $categoryData
     * @param string $message
     */
    private function assertCategoryInCategoryData(int $categoryId, array $categoryData, string $message): void
    {
        $ids = array_column($categoryData, 'categoryId');
        $this->assertContains((string)$categoryId, $ids, $message);
    }

    /**
     * @param int $categoryId
     * @param array $categoryData
     * @param string $message
     */
    private function assertCategoryNotInCategoryData(int $categoryId, array $categoryData, string $message): void
    {
        $ids = array_column($categoryData, 'categoryId');
        $this->assertNotContains((string)$categoryId, $ids, $message);
    }

    /**
     * @param int $categoryId
     * @param string $expectedPath
     * @param array $categoryData
     */
    private function assertCategoryPath(int $categoryId, string $expectedPath, array $categoryData): void
    {
        foreach ($categoryData as $entry) {
            if ((string)$entry['categoryId'] === (string)$categoryId) {
                $this->assertEquals(
                    $expectedPath,
                    $entry['categoryPath'],
                    sprintf('categoryPath mismatch for category %d', $categoryId)
                );
                return;
            }
        }
        $this->fail(sprintf('Category %d not found in categoryData for path assertion', $categoryId));
    }
}
