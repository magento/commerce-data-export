<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CategoryProductDataExporter\Test\Integration;

use AdobeCommerce\CategoryProductDataExporter\Model\Provider\Product\CategoryData as NonIndexCategoryDataProvider;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogDataExporter\Model\Provider\Product\CategoryData;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The two scoping rules below are enforced by Magento core's own index builder
 * (Magento\Catalog\Model\Indexer\Category\Product\AbstractAction::getNonAnchorCategoriesSelect())
 * and must not silently change if this provider, or any override of it, is touched:
 *  - is_active is checked only on the category the product is directly assigned to, never on
 *    ancestors - an active category under a disabled parent still gets its product assignments
 *    exported.
 *  - a product that is Disabled, or Not Visible Individually, never gets a row in the index at
 *    all, regardless of how active the category is.
 */
#[
    DbIsolation(false),
    AppIsolation(true),
]
class CategoryDataScopeTest extends TestCase
{
    private const STORE_VIEW_CODE = 'default';

    private CategoryData|NonIndexCategoryDataProvider $provider;

    private DataFixtureStorage $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = Bootstrap::getObjectManager()->get(CategoryData::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    /**
     * Tree: Default Category (2) -> disabledParent (is_active=0) -> activeChild (is_active=1);
     * product is assigned directly to activeChild only.
     *
     * The index only checks is_active on the category the product is directly assigned to, so
     * activeChild must still appear in categoryData even though its parent is disabled.
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2, 'is_active' => false], 'disabledParent'),
        DataFixture(
            CategoryFixture::class,
            ['parent_id' => '$disabledParent.id$', 'is_active' => true],
            'activeChild'
        ),
        DataFixture(ProductFixture::class, ['category_ids' => ['$activeChild.id$']], 'product'),
    ]
    public function testActiveCategoryUnderDisabledParentStillExported(): void
    {
        $activeChildId = (int)$this->fixtures->get('activeChild')->getId();

        $rows = $this->getCategoryRows((int)$this->fixtures->get('product')->getId());

        $this->assertArrayHasKey(
            $activeChildId,
            $rows,
            'An active category directly assigned to the product must be exported '
            . 'even when an ancestor category is disabled.'
        );
    }

    /**
     * Category is active; product is Disabled. The index never writes a row for a disabled
     * product, so categoryData must not contain the category it is (still) assigned to.
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2], 'category'),
        DataFixture(
            ProductFixture::class,
            ['status' => Status::STATUS_DISABLED, 'category_ids' => ['$category.id$']],
            'product'
        ),
    ]
    public function testDisabledProductExcludedFromCategoryData(): void
    {
        $categoryId = (int)$this->fixtures->get('category')->getId();

        $rows = $this->getCategoryRows((int)$this->fixtures->get('product')->getId());

        $this->assertArrayNotHasKey(
            $categoryId,
            $rows,
            'A disabled product must not be exported under any category, even one it is directly assigned to.'
        );
    }

    /**
     * Category is active; product visibility is "Not Visible Individually". The index never
     * writes a row for a not-visible product, so categoryData must not contain the category it
     * is (still) assigned to.
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2], 'category'),
        DataFixture(
            ProductFixture::class,
            ['visibility' => Visibility::VISIBILITY_NOT_VISIBLE, 'category_ids' => ['$category.id$']],
            'product'
        ),
    ]
    public function testNotVisibleIndividuallyProductExcludedFromCategoryData(): void
    {
        $categoryId = (int)$this->fixtures->get('category')->getId();

        $rows = $this->getCategoryRows((int)$this->fixtures->get('product')->getId());

        $this->assertArrayNotHasKey(
            $categoryId,
            $rows,
            'A product not visible individually must not be exported under any category.'
        );
    }

    /**
     * Runs the provider for a single product and indexes the returned rows by category id.
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
