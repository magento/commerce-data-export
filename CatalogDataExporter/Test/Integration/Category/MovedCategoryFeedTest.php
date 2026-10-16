<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Category;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Verifies that the category feed correctly reflects the new urlPath after a category is moved
 * to a different position in the hierarchy
 *
 * Fixture structure (root category id = 2):
 *   cat1-move  (810)           url_path = "cat1-move"
 *     cat1-child-move  (811)   url_path = "cat1-move/cat1-child-move"
 *   cat2-move  (812)           url_path = "cat2-move"
 *     cat2-child-move  (813)   url_path = "cat2-move/cat2-child-move"
 *
 * After moving 810 under 813:
 *   cat2-move  (812)
 *     cat2-child-move  (813)
 *       cat1-move  (810)  → expected url_path = "cat2-move/cat2-child-move/cat1-move"
 *
 * @magentoAppArea adminhtml
 */
class MovedCategoryFeedTest extends AbstractCategoryTestCase
{
    private const CAT1_ID = 810;
    private const CAT1_CHILD_ID = 811;
    private const CAT2_CHILD_ID = 813;
    private const STORE_VIEW_CODES = ['default', 'fixture_second_store'];
    private const EXPECTED_URL_PATH_AFTER_MOVE = 'cat2-move/cat2-child-move/cat1-move';
    private const EXPECTED_CHILD_URL_PATH_AFTER_MOVE = 'cat2-move/cat2-child-move/cat1-move/cat1-child-move';

    /**
     * After moving a category and running an incremental reindex, the category feed must contain
     * the updated urlPath for the moved category.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_category_move.php
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testMovedCategoryUrlPathUpdatedAfterReindex(): void
    {
        $this->emulatePartialReindexBehavior([self::CAT1_ID]);
        $initialData = $this->getCategoryById(self::CAT1_ID, self::STORE_VIEW_CODES[0]);
        $this->assertNotEmpty($initialData, 'Category 810 must be in the feed before the move.');
        $this->assertEquals(
            'cat1-move',
            $initialData['urlPath'],
            'Initial urlPath must equal the category url_key.'
        );

        // emulate admin controller
        $cat1 = ObjectManager::getInstance()->create(\Magento\Catalog\Model\Category::class);
        $cat1->setStoreId(0);
        $cat1->load(self::CAT1_ID);

        $cat1->move(self::CAT2_CHILD_ID, null);

        $this->emulatePartialReindexBehavior([self::CAT1_ID, self::CAT1_CHILD_ID]);

        foreach (self::STORE_VIEW_CODES as $storeCode) {
            $afterMove = $this->getCategoryById(self::CAT1_ID, $storeCode);
            $this->assertNotEmpty(
                $afterMove,
                "Category 810 must still be present in the feed for $storeCode after being moved."
            );
            $this->assertEquals(
                self::EXPECTED_URL_PATH_AFTER_MOVE,
                $afterMove['urlPath'],
                "urlPath for $storeCode must reflect the new position in the hierarchy after the move."
            );

            $childAfterMove = $this->getCategoryById(self::CAT1_CHILD_ID, $storeCode);
            $this->assertNotEmpty(
                $childAfterMove,
                "Category 811 (child of moved category) must be present in the feed for $storeCode after the move."
            );
            $this->assertEquals(
                self::EXPECTED_CHILD_URL_PATH_AFTER_MOVE,
                $childAfterMove['urlPath'],
                "Child category urlPath for $storeCode must reflect the new hierarchy after the parent was moved."
            );
        }
    }
}
