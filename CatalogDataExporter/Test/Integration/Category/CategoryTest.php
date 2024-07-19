<?php
/**
 * Copyright 2023 Adobe
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

namespace Magento\CatalogDataExporter\Test\Integration\Category;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Test class for category feed
 */
class CategoryTest extends AbstractCategoryTest
{
    /**
     * Validate categories in different store views
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_category_tree.php
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testCategoriesInDifferentStoreViews() : void
    {
        $currentStore = $this->storeManager->getStore();
        $storeDefault = $this->storeManager->getStore('default');
        $storeCustomOne = $this->storeManager->getStore('custom_store_view_one');
        $storeCustomTwo = $this->storeManager->getStore('custom_store_view_two');

        $categoryIdsInCustomStore = [400, 401, 402];
        $categoryIdsInDefaultStore = [500, 501, 502];

        try {
            foreach ($categoryIdsInCustomStore as $categoryId) {
                foreach ([$storeCustomOne, $storeCustomTwo] as $store) {
                    $this->storeManager->setCurrentStore($store);
                    $category = $this->categoryRepository->get($categoryId, $store->getId());
                    $this->emulatePartialReindexBehavior([$categoryId]);
                    $extractedCategoryData = $this->getCategoryById($categoryId, $store->getCode());
                    $this->assertBaseCategoryData($category, $extractedCategoryData, $store);
                }
            }

            foreach ($categoryIdsInDefaultStore as $categoryId) {
                $this->storeManager->setCurrentStore($storeDefault);
                $category = $this->categoryRepository->get($categoryId, $storeDefault->getId());
                $this->emulatePartialReindexBehavior([$categoryId]);
                $extractedCategoryData = $this->getCategoryById($categoryId, $storeDefault->getCode());
                $this->assertBaseCategoryData($category, $extractedCategoryData, $storeDefault);
            }
        } finally {
            $this->storeManager->setCurrentStore($currentStore);
        }
    }
}
