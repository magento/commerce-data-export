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

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\CatalogDataExporter\Model\Provider\Product\CategoryData;
use AdobeCommerce\CategoryProductDataExporter\Model\Provider\Product\CategoryData as NonIndexCategoryDataProvider;

/**
 * Integration coverage for anchor-category product assignment in the category-product data feed.
 *
 * A product assigned only to a child category must be reported both under that child as a direct
 * assignment (productPosition 0) and under any ancestor with is_anchor=1 as a synthetic,
 * anchor-inherited assignment (productPosition 10000). It must NOT be pulled up under a non-anchor
 * ancestor. These are exactly the cases CategoryData::expandWithAnchorCategories() is responsible for.
 */
#[DbIsolation(false)]
class AnchorCategoryProductTest extends TestCase
{
    /**
     * Synthetic position the provider assigns to anchor-inherited rows.
     */
    private const ANCHOR_POSITION = 10000;

    private const STORE_VIEW_CODE = 'default';

    /**
     * @var CategoryData
     */
    private CategoryData|NonIndexCategoryDataProvider $provider;

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
     * A product assigned only to the child rolls up to the is_anchor=1 parent.
     *
     * Tree: Default Category (2) -> anchor (is_anchor=1) -> child (is_anchor=0); product in child only.
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2, 'is_anchor' => 1], 'anchor'),
        DataFixture(CategoryFixture::class, ['parent_id' => '$anchor.id$', 'is_anchor' => 0], 'child'),
        DataFixture(ProductFixture::class, ['category_ids' => ['$child.id$']], 'product'),
    ]
    public function testProductRollsUpToAnchorParent(): void
    {
        $anchorId = (int)$this->fixtures->get('anchor')->getId();
        $childId = (int)$this->fixtures->get('child')->getId();
        $productId = (int)$this->fixtures->get('product')->getId();

        $rows = $this->getCategoryRows($productId);

        $this->assertArrayHasKey(
            $childId,
            $rows,
            'Product must be reported under its directly assigned child category.'
        );
        $this->assertSame(
            0,
            (int)$rows[$childId]['productPosition'],
            'Direct assignment must keep its own catalog_category_product position (0).'
        );

        $this->assertArrayHasKey(
            $anchorId,
            $rows,
            'Product assigned only to the child must roll up to the is_anchor=1 parent category.'
        );
        $this->assertSame(
            self::ANCHOR_POSITION,
            (int)$rows[$anchorId]['productPosition'],
            'Anchor-inherited assignment must use the synthetic anchor position.'
        );
    }

    /**
     * The same product must NOT be pulled up under a non-anchor (is_anchor=0) parent.
     *
     * Tree: Default Category (2) -> plain (is_anchor=0) -> child (is_anchor=0); product in child only.
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2, 'is_anchor' => 0], 'plain'),
        DataFixture(CategoryFixture::class, ['parent_id' => '$plain.id$', 'is_anchor' => 0], 'child'),
        DataFixture(ProductFixture::class, ['category_ids' => ['$child.id$']], 'product'),
    ]
    public function testProductDoesNotRollUpToNonAnchorParent(): void
    {
        $plainId = (int)$this->fixtures->get('plain')->getId();
        $childId = (int)$this->fixtures->get('child')->getId();
        $productId = (int)$this->fixtures->get('product')->getId();

        $rows = $this->getCategoryRows($productId);

        $this->assertArrayHasKey(
            $childId,
            $rows,
            'Product must be reported under its directly assigned child category.'
        );
        $this->assertArrayNotHasKey(
            $plainId,
            $rows,
            'Product must NOT roll up to a non-anchor (is_anchor=0) parent category.'
        );
    }

    /**
     * Two products in two sibling child categories both roll up to the shared anchor parent.
     *
     * Tree: Default Category (2) -> anchor (is_anchor=1) -> {childA, childB}; productA in childA,
     * productB in childB. The anchor parent must aggregate both products, each at the anchor position.
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2, 'is_anchor' => 1], 'anchor'),
        DataFixture(CategoryFixture::class, ['parent_id' => '$anchor.id$', 'is_anchor' => 0], 'childA'),
        DataFixture(CategoryFixture::class, ['parent_id' => '$anchor.id$', 'is_anchor' => 0], 'childB'),
        DataFixture(ProductFixture::class, ['category_ids' => ['$childA.id$']], 'productA'),
        DataFixture(ProductFixture::class, ['category_ids' => ['$childB.id$']], 'productB'),
    ]
    public function testAnchorParentAggregatesProductsFromAllChildren(): void
    {
        $anchorId = (int)$this->fixtures->get('anchor')->getId();
        $productAId = (int)$this->fixtures->get('productA')->getId();
        $productBId = (int)$this->fixtures->get('productB')->getId();

        foreach ([$productAId, $productBId] as $productId) {
            $rows = $this->getCategoryRows($productId);
            $this->assertArrayHasKey(
                $anchorId,
                $rows,
                "Product $productId must roll up to the shared anchor parent."
            );
            $this->assertSame(
                self::ANCHOR_POSITION,
                (int)$rows[$anchorId]['productPosition'],
                "Product $productId must roll up to the anchor parent at the anchor position."
            );
        }
    }

    /**
     * The anchor-inherited position must be min(child position) + 10000, not a flat 10000.
     *
     * Mirrors the catalog_category_product_index formula IFNULL(ccp2.position, MIN(ccp.position) + 10000)
     * (Magento\Catalog\Model\Indexer\Category\Product\AbstractAction): a product rolled up into an anchor
     * from a descendant carries that descendant's position offset by 10000, preserving relative ordering.
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2, 'is_anchor' => 1], 'anchor'),
        DataFixture(CategoryFixture::class, ['parent_id' => '$anchor.id$', 'is_anchor' => 0], 'child'),
        DataFixture(ProductFixture::class, ['category_ids' => ['$child.id$']], 'product'),
    ]
    public function testAnchorPositionIsMinChildPositionPlusOffset(): void
    {
        $anchorId = (int)$this->fixtures->get('anchor')->getId();
        $childId = (int)$this->fixtures->get('child')->getId();
        $productId = (int)$this->fixtures->get('product')->getId();

        // Give the direct child assignment a non-zero position so a flat vs. offset position differs.
        $connection = Bootstrap::getObjectManager()->get(ResourceConnection::class)->getConnection();
        $connection->update(
            $connection->getTableName('catalog_category_product'),
            ['position' => 7],
            ['category_id = ?' => $childId, 'product_id = ?' => $productId]
        );

        $rows = $this->getCategoryRows($productId);

        $this->assertSame(
            7,
            (int)$rows[$childId]['productPosition'],
            'The direct child assignment must keep its own position.'
        );
        $this->assertSame(
            7 + self::ANCHOR_POSITION,
            (int)$rows[$anchorId]['productPosition'],
            'The anchor-inherited position must be the min child position (7) plus the 10000 offset.'
        );
    }

    /**
     * Tree: Default Category (2) -> anchor (is_anchor=1, is_active=1)
     *       -> disabledMiddle (is_active=0) -> leaf (is_active=1); product assigned to leaf only.
     *
     * Per core Magento (is_active is checked only on the category a product is directly assigned
     * to, and only on the anchor itself for rollup - never on an intermediate ancestor), BOTH the
     * leaf assignment and the anchor rollup should be exported. filterInactiveCategories() instead
     * requires every ancestor (including disabledMiddle) to be active, drops the leaf row before
     * expandWithAnchorCategories() ever runs, and the anchor rollup silently disappears too.
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2, 'is_anchor' => 1], 'anchor'),
        DataFixture(
            CategoryFixture::class,
            ['parent_id' => '$anchor.id$', 'is_active' => false],
            'disabledMiddle'
        ),
        DataFixture(
            CategoryFixture::class,
            ['parent_id' => '$disabledMiddle.id$', 'is_active' => true],
            'leaf'
        ),
        DataFixture(ProductFixture::class, ['category_ids' => ['$leaf.id$']], 'product'),
    ]
    public function testAnchorRollupSurvivesInactiveIntermediateAncestor(): void
    {
        $leafId = (int)$this->fixtures->get('leaf')->getId();
        $anchorId = (int)$this->fixtures->get('anchor')->getId();

        $rows = $this->getCategoryRows((int)$this->fixtures->get('product')->getId());

        $this->assertArrayHasKey(
            $leafId,
            $rows,
            'BUG: leaf (is_active=1) was dropped because an intermediate ancestor is inactive - '
            . 'core only checks is_active on the directly assigned category.'
        );
        $this->assertArrayHasKey(
            $anchorId,
            $rows,
            'BUG: anchor rollup disappeared because the leaf row never survived filterInactiveCategories(), '
            . 'even though the anchor itself is active.'
        );
    }

    /**
     * Tree: Default Category (2) -> anchor (is_anchor=1, is_active=1) -> child (is_active=1);
     * product is Disabled, assigned to child only.
     *
     * Per core Magento, a disabled product never gets an index row at all - neither the direct
     * child assignment nor the anchor rollup. filterInactiveCategories() only checks category
     * is_active (both child and anchor are active here), so the disabled product's row survives
     * filtering and expandWithAnchorCategories() then ALSO rolls it up onto the anchor - doubling
     * the incorrect exposure (child AND anchor both wrongly show the disabled product).
     */
    #[
        DataFixture(CategoryFixture::class, ['parent_id' => 2, 'is_anchor' => 1], 'anchor'),
        DataFixture(CategoryFixture::class, ['parent_id' => '$anchor.id$', 'is_anchor' => 0], 'child'),
        DataFixture(
            ProductFixture::class,
            ['status' => Status::STATUS_DISABLED, 'category_ids' => ['$child.id$']],
            'product'
        ),
    ]
    public function testDisabledProductExcludedFromAnchorRollupToo(): void
    {
        $childId = (int)$this->fixtures->get('child')->getId();
        $anchorId = (int)$this->fixtures->get('anchor')->getId();

        $rows = $this->getCategoryRows((int)$this->fixtures->get('product')->getId());

        $this->assertArrayNotHasKey(
            $childId,
            $rows,
            'BUG: a disabled product must not be exported under its directly assigned category.'
        );
        $this->assertArrayNotHasKey(
            $anchorId,
            $rows,
            'BUG: a disabled product must not be rolled up onto an anchor ancestor either - '
            . 'filterInactiveCategories() never checks product status/visibility, so '
            . 'expandWithAnchorCategories() rolls the disabled product up anyway.'
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
