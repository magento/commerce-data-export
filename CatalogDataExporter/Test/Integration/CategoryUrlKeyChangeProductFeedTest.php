<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Indexer\Model\Processor;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Verifies that product feed returns correct categoryData when a category assigned to the product
 * has its parent category's url_key changed. Child categories must reflect the new path.
 *
 * Example: cat1 (parent) has child cat2; product is in cat2 with categoryPath "cat1/cat2".
 * After changing cat1 url_key to "cat1-new", product must have categoryPath "cat1-new/cat2" for cat2.
 *
 * @magentoAppArea adminhtml
 */
class CategoryUrlKeyChangeProductFeedTest extends AbstractProductTestHelper
{
    /**
     * Category IDs from Magento/Catalog/_files/categories.php:
     * - 3: Category 1 (parent), url_key category-1
     * - 4: Category 1.1 (child of 3), url_key category-1-1
     * - 13: Category 1.2 (child of 3), url_key category-1-2
     * Product 'simple' is assigned to categories [2, 3, 4, 13].
     */
    private const PARENT_CATEGORY_ID = '3';
    private const CHILD_CATEGORY_ID_1 = '4';
    private const CHILD_CATEGORY_ID_2 = '13';
    private const PRODUCT_SKU = 'simple';
    private const NEW_PARENT_URL_KEY = 'category-1-new';

    /**
     * @var Processor
     */
    private $indexerProcessor;

    protected function setUp(): void
    {
        $this->indexerProcessor = Bootstrap::getObjectManager()->create(Processor::class);

        parent::setUp();
    }

    /**
     * Product feed must return updated categoryPath in categoryData when parent category url_key changes.
     * Child categories (4, 13) must show path with the new parent url_key.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/categories.php
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testProductFeedReflectsParentCategoryUrlKeyChangeInCategoryData(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $storeManager = $objectManager->get(StoreManagerInterface::class);

        // Change parent category (Category 1, id 3) url_key
        $parentCategory = $this->categoryRepository->get(self::PARENT_CATEGORY_ID);
        $parentCategory->setStoreId($storeManager->getDefaultStoreView()->getId());
        $parentCategory->setUrlKey(self::NEW_PARENT_URL_KEY);
        $parentCategory->setUrlPath(self::NEW_PARENT_URL_KEY);
        $this->categoryRepository->save($parentCategory);

        $this->indexerProcessor->updateMview();
        $this->indexerProcessor->reindexAllInvalid();

        $extractedProductData = $this->getExtractedProduct(self::PRODUCT_SKU, 'default');
        $this->assertNotEmpty(
            $extractedProductData,
            'Product with SKU ' . self::PRODUCT_SKU . ' not found in the feed.'
        );
        $this->assertNotEmpty(
            $extractedProductData['feedData']['categoryData'],
            'categoryData must not be empty.'
        );

        $feedData = $extractedProductData['feedData'];
        $categoryData = $feedData['categoryData'];

        $expectedByCategoryId = [
            self::PARENT_CATEGORY_ID => [
                'categoryId' => self::PARENT_CATEGORY_ID,
                'categoryPath' => self::NEW_PARENT_URL_KEY,
                'productPosition' => 0,
            ],
            self::CHILD_CATEGORY_ID_1 => [
                'categoryId' => self::CHILD_CATEGORY_ID_1,
                'categoryPath' => self::NEW_PARENT_URL_KEY . '/category-1-1',
                'productPosition' => 0,
            ],
            self::CHILD_CATEGORY_ID_2 => [
                'categoryId' => self::CHILD_CATEGORY_ID_2,
                'categoryPath' => self::NEW_PARENT_URL_KEY . '/category-1-2',
                'productPosition' => 0,
            ],
        ];

        $actualByCategoryId = $this->normalizeCategoryDataForComparison($categoryData);
        $this->assertEquals(
            $expectedByCategoryId,
            $actualByCategoryId,
            'categoryData must reflect new parent url_key for the changed category and its children.'
        );
    }

    /**
     * Normalize feed categoryData to expected shape
     *
     * @param array $categoryData
     * @return array
     */
    private function normalizeCategoryDataForComparison(array $categoryData): array
    {
        $result = [];
        foreach ($categoryData as $item) {
            $categoryId = (string) $item['categoryId'];
            $result[$categoryId] = [
                'categoryId' => $categoryId,
                'categoryPath' => (string) ($item['categoryPath'] ?? ''),
                'productPosition' => (int) ($item['productPosition'] ?? 0),
            ];
        }
        ksort($result);
        return $result;
    }
}
