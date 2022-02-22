<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Category;

use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

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
        $data = $this->getCategoryById(501, 'default');
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
        $data = $this->getCategoryById(402, 'custom_store_view_one');
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
        $data = $this->getCategoryById(402, 'custom_store_view_two');

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
     * Asserts that actual breadcrumbs data is equal to expected data.
     *
     * @param array $categoryBreadCrumbs
     * @param array $expectedBreadCrumbs
     *
     * @return void
     *
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    private function assertContainsSpecifiedData(array $categoryBreadCrumbs, array $expectedBreadCrumbs): void
    {
        // Sort breadcrumbs by category level
        \usort($categoryBreadCrumbs, function ($a, $b) {
            return $a['categoryLevel'] <=> $b['categoryLevel'];
        });

        self::assertEquals($expectedBreadCrumbs, $categoryBreadCrumbs);
    }

    /**
     * @param array $data
     */
    private function assertContainsBreadCrumbs(array $data): void
    {
        $this->assertIsArray($data);
        $this->assertArrayHasKey('breadcrumbs', $data);
        $this->assertIsArray($data['breadcrumbs']);
    }
}
