<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Category;

/**
 * Test class for category feed breadcrumbs data
 */
class CategoryBreadCrumbsTest extends AbstractCategoryTest
{
    /**
     * Validate breadcrumbs content
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/CatalogDataExporter/_files/setup_category_tree.php
     *
     * @return void
     */
    public function testBreadCrumbsData(): void
    {
        //store 1, default store-view
        $data = $this->categoryFeed->getFeedByIds([501], ['default'])['feed'][0];
        $this->assertContainsBreadCrumbs($data);
        $breadCrumbs = $data['breadcrumbs'];
        $expected = [
            [
                'categoryId' => '500',
                'categoryName' => 'Category main 1',
                'categoryLevel' => 2,
                'categoryUrlKey' => 'category-main-1',
                'categoryUrlPath' => 'category-main-1'
            ]
        ];
        $this->assertContainsSpecifiedData($breadCrumbs, $expected);

        //store 2, first store-view
        $data = $this->categoryFeed->getFeedByIds([402], ['custom_store_view_one'])['feed'][0];
        $this->markTestSkipped('should be fixed in https://github.com/magento/catalog-storefront/issues/406');
        $this->assertContainsBreadCrumbs($data);
        $breadCrumbs = $data['breadcrumbs'];
        $expected = [
            [
                'categoryId' => '400',
                'categoryName' => 'Category 1_custom_store_view_one',
                'categoryLevel' => 2,
                'categoryUrlKey' => 'category-1_custom_store_view_one',
                'categoryUrlPath' => 'category-1'
            ],
            [
                'categoryId' => '401',
                'categoryName' => 'Category 1.1_custom_store_view_one',
                'categoryLevel' => 3,
                'categoryUrlKey' => 'category-1-1_custom_store_view_one',
                'categoryUrlPath' => 'category-1/category-1-1'
            ]
        ];
        $this->assertContainsSpecifiedData($breadCrumbs, $expected);

        //store 2, second store-view
        $data = $this->categoryFeed->getFeedByIds([402], ['custom_store_view_two'])['feed'][0];

        $this->assertContainsBreadCrumbs($data);
        $breadCrumbs = $data['breadcrumbs'];
        $expected = [
            [
                'categoryId' => '400',
                'categoryName' => 'Category 1_custom_store_view_two',
                'categoryLevel' => 2,
                'categoryUrlKey' => 'category-1_custom_store_view_two',
                'categoryUrlPath' => 'category-1'
            ],
            [
                'categoryId' => '401',
                'categoryName' => 'Category 1.1_custom_store_view_two',
                'categoryLevel' => 3,
                'categoryUrlKey' => 'category-1-1_custom_store_view_two',
                'categoryUrlPath' => 'category-1/category-1-1'
            ]
        ];
        $this->assertContainsSpecifiedData($breadCrumbs, $expected);
    }

    /**
     * @param array $categoryBreadCrumbs
     * @param array $expectedBreadCrumbs
     */
    private function assertContainsSpecifiedData(array $categoryBreadCrumbs, array $expectedBreadCrumbs): void
    {
        usort($categoryBreadCrumbs, function ($a, $b) {
            return $a['categoryId'] <=> $b['categoryId'];
        });
        usort($expectedBreadCrumbs, function ($a, $b) {
            return $a['categoryId'] <=> $b['categoryId'];
        });
        $this->assertEquals(
            $expectedBreadCrumbs,
            $categoryBreadCrumbs
        );
    }

    /**
     * @param array $data
     */
    private function assertContainsBreadCrumbs(array $data): void
    {
        $this->assertIsArray($data);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('breadcrumbs', $data);
        $this->assertIsArray($data['breadcrumbs']);
    }
}
