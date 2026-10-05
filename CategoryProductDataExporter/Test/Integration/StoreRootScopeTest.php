<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace AdobeCommerce\CategoryProductDataExporter\Test\Integration;

use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use AdobeCommerce\CategoryProductDataExporter\Model\Provider\Product\CategoryData;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The category-product feed for a store view must only include categories under that store view's root.
 *
 * A product-category assignment is a plain, website-agnostic catalog_category_product row, so a product
 * can be assigned to a category that lives under a *different* root category than the store view's root.
 * The index-based provider never exposed such categories for a store view (the index is built only from
 * the store's root tree), and the write-side provider must match that by scoping to the store root.
 */
#[
    AppIsolation(true),
    DbIsolation(false)
]
class StoreRootScopeTest extends TestCase
{
    private const STORE_VIEW_CODE = 'default';

    /**
     * @var CategoryData
     */
    private CategoryData $provider;

    /**
     * @var DataFixtureStorage
     */
    private DataFixtureStorage $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = Bootstrap::getObjectManager()->get(CategoryData::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    /**
     * A category from a second root tree (under Root Catalog id 1, outside the Default Category id 2 root)
     * must be excluded from the default store view feed, even though the product is directly assigned to it.
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2], 'inRootChild'),
        DataFixture(CategoryFixture::class, ['parent_id' => 1], 'foreignRoot'),
        DataFixture(CategoryFixture::class, ['parent_id' => '$foreignRoot.id$'], 'foreignChild'),
        DataFixture(
            ProductFixture::class,
            ['category_ids' => ['$inRootChild.id$', '$foreignChild.id$']],
            'product'
        ),
    ]
    public function testCategoryOutsideStoreRootTreeIsExcluded(): void
    {
        $inRootChildId = (int)$this->fixtures->get('inRootChild')->getId();
        $foreignChildId = (int)$this->fixtures->get('foreignChild')->getId();
        $productId = (int)$this->fixtures->get('product')->getId();

        $rows = $this->getCategoryRows($productId);

        $this->assertArrayHasKey(
            $inRootChildId,
            $rows,
            'A category under the store view root must be present in the feed.'
        );
        $this->assertArrayNotHasKey(
            $foreignChildId,
            $rows,
            'A category from a different root tree must not appear in the store view feed.'
        );
    }

    /**
     * Runs the provider for a single product and indexes the returned feed rows by category id.
     *
     * @param int $productId
     * @return array<int, array>
     */
    private function getCategoryRows(int $productId): array
    {
        $output = $this->provider->get([
            ['productId' => $productId, 'storeViewCode' => self::STORE_VIEW_CODE],
        ]);

        $rows = [];
        foreach ($output as $row) {
            $rows[(int)$row['categoryData']['categoryId']] = $row['categoryData'];
        }

        return $rows;
    }
}
