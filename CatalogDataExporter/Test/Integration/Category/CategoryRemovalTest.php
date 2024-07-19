<?php
/**
 * Copyright 2024 Adobe
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

use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for category feed removal
 */
class CategoryRemovalTest extends AbstractCategoryTest
{
    /**
     * Validate category removal
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_category_removal.php
     *
     * @return void
     */
    public function testCategoryRemoval() : void
    {
        $categoryId = 600;

        $extractedCategory = $this->getCategoryById($categoryId, 'default');
        $this->assertNotEmpty($extractedCategory, "Exported Category Data is empty");
        $this->assertEquals(false, $extractedCategory['deleted']);

        $this->deleteCategory($categoryId);
        $this->emulateCustomersBehaviorAfterDeleteAction();
        $this->emulatePartialReindexBehavior([$categoryId]);

        $extractedCategory = $this->getCategoryById($categoryId, 'default');
        $this->assertTrue($extractedCategory['deleted'], "Category is not set as deleted");
    }

    /**
     * Delete category
     *
     * @param int $categoryId
     */
    private function deleteCategory(int $categoryId) : void
    {
        /** @var \Magento\Framework\Registry $registry */
        $registry = Bootstrap::getObjectManager()->get(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);

        $this->categoryRepository->deleteByIdentifier($categoryId);

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);
    }
}
