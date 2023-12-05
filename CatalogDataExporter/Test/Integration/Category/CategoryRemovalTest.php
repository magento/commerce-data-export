<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
