<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogUrlRewriteDataExporter\Test\Integration;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\CatalogDataExporter\Test\Integration\Category\AbstractCategoryTest;

/**
 * Test for categories urls export
 */
class CategoryUrlsTest extends AbstractCategoryTest
{
    /**
     * Validate category URL data
     * @magentoConfigFixture custom_store_view_one_store catalog/seo/category_canonical_tag 1
     * @magentoConfigFixture custom_store_view_two_store catalog/seo/category_canonical_tag 1
     * @magentoConfigFixture default_store catalog/seo/category_canonical_tag 1
     * @magentoDataFixture Magento_CatalogDataExporter::Test/Integration/_files/setup_category_tree.php
     *
     * @return void
     */
    public function testCategoriesUrls() : void
    {
        $this->runIndexer([400, 401, 402, 500, 501, 502]);

        $storeDefault = $this->storeManager->getStore('default');
        $storeCustomOne = $this->storeManager->getStore('custom_store_view_one');
        $storeCustomTwo = $this->storeManager->getStore('custom_store_view_two');

        foreach ([400, 401, 402] as $categoryId) {
            foreach ([$storeCustomOne, $storeCustomTwo] as $store) {
                $this->validateUrlData(
                    $this->categoryRepository->get($categoryId, $store->getId()),
                    $this->categoryFeed->getFeedByIds([$categoryId], [$store->getCode()])['feed'][0]
                );
            }
        }

        foreach ([500, 501, 502] as $categoryId) {
            $this->validateUrlData(
                $this->categoryRepository->get($categoryId, $storeDefault->getId()),
                $this->categoryFeed->getFeedByIds([$categoryId], [$storeDefault->getCode()])['feed'][0]
            );
        }
    }

    /**
     * Validate URL data in extracted category data
     *
     * @param CategoryInterface $category
     * @param array $extractedProduct
     */
    private function validateUrlData(CategoryInterface $category, array $extractedProduct) : void
    {
        $canonicalUrl = \str_replace($category->getUrlInstance()->getBaseUrl(), '', $category->getUrl());
        $this->assertEquals($canonicalUrl, $extractedProduct['canonicalUrl']);
    }
}
