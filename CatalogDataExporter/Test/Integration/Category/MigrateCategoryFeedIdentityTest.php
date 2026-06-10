<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogDataExporter\Setup\Patch\Data\MigrateCategoryFeedIdentity;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for MigrateCategoryFeedIdentity data patch.
 *
 * Verifies that the patch clears feed rows for existing categories and invalidates
 * the indexer so a subsequent reindex rebuilds everything under the new urlPath identity
 * without generating same-slug delete rows (the deploy-time delete storm).
 */
class MigrateCategoryFeedIdentityTest extends TestCase
{
    private const FEED_TABLE = 'cde_categories_feed';
    private const FEED_INDEXER = 'catalog_data_exporter_categories';

    /** @var ResourceConnection */
    private ResourceConnection $resource;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    private \Magento\Framework\DB\Adapter\AdapterInterface $connection;

    /** @var Indexer */
    private Indexer $indexer;

    /** @var CategoryRepositoryInterface */
    private CategoryRepositoryInterface $categoryRepository;

    /** @var MigrateCategoryFeedIdentity */
    private MigrateCategoryFeedIdentity $patch;

    /** @var int */
    private int $testCategoryId;

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        // Must run before resolving any service that depends on CategoryFeedIndexMetadata
        // so that persistExportedFeed is applied before the metadata object is instantiated.
        $objectManager->configure([
            'Magento\CatalogDataExporter\Model\Indexer\CategoryFeedIndexMetadata' => [
                'arguments' => ['persistExportedFeed' => true]
            ]
        ]);

        $this->resource = $objectManager->get(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
        $this->indexer = $objectManager->get(Indexer::class);
        $this->categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);
        $this->patch = $objectManager->get(MigrateCategoryFeedIdentity::class);

        // Create a test category with a unique url_key per test method to avoid conflicts
        $urlKey = 'comopt2039-migration-test-' . substr(hash('sha256', $this->name()), 0, 8);
        $category = $objectManager->get(CategoryFactory::class)->create();
        $category->setName('COMOPT2039 Migration Test');
        $category->setUrlKey($urlKey);
        $category->setIsActive(true);
        $category->setParentId(2);
        $category = $this->categoryRepository->save($category);
        $this->testCategoryId = (int)$category->getId();

        // Index the category to populate the feed table
        $this->indexer->load(self::FEED_INDEXER);
        $this->indexer->reindexList([$this->testCategoryId]);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    protected function tearDown(): void
    {
        if ($this->testCategoryId) {
            $this->connection->delete(
                $this->resource->getTableName(self::FEED_TABLE),
                ['source_entity_id = ?' => $this->testCategoryId]
            );
            // Clean up category EAV rows directly to avoid staging delete restriction
            $rowIds = $this->connection->fetchCol(
                "SELECT row_id FROM {$this->resource->getTableName('catalog_category_entity')}
                 WHERE entity_id = ?",
                [$this->testCategoryId]
            );
            if ($rowIds) {
                $rowIdList = implode(',', $rowIds);
                foreach (['catalog_category_entity_varchar', 'catalog_category_entity_int',
                          'catalog_category_entity_text', 'catalog_category_entity_decimal',
                          'catalog_category_entity_datetime'] as $table) {
                    $this->connection->delete(
                        $this->resource->getTableName($table),
                        "row_id IN ($rowIdList)"
                    );
                }
                $this->connection->delete(
                    $this->resource->getTableName('catalog_category_entity'),
                    "row_id IN ($rowIdList)"
                );
                // Keep parent children_count consistent so other tests see a clean tree
                $this->connection->query(
                    "UPDATE {$this->resource->getTableName('catalog_category_entity')}
                     SET children_count = GREATEST(children_count - 1, 0)
                     WHERE entity_id = 2"
                );
            }
            $this->connection->delete(
                $this->resource->getTableName('url_rewrite'),
                ['entity_id = ?' => $this->testCategoryId, 'entity_type = ?' => 'category']
            );
            if ($this->connection->isTableExists($this->resource->getTableName('sequence_catalog_category'))) {
                $this->connection->delete(
                    $this->resource->getTableName('sequence_catalog_category'),
                    ['sequence_value = ?' => $this->testCategoryId]
                );
            }
        }
    }

    /**
     * Main scenario: patch clears feed rows for existing categories; reindex rebuilds with
     * correct identity and produces no is_deleted rows.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testPatchClearsFeedRowsAndPreventsDeleteStormOnReindex(): void
    {
        $this->assertNotEmpty($this->fetchFeedRows(), 'Feed row must exist after partial reindex');

        $this->patch->apply();

        $this->assertEmpty(
            $this->fetchFeedRows(),
            'Patch must remove all feed rows for existing categories'
        );
        $this->indexer->load(self::FEED_INDEXER);
        $this->assertTrue($this->indexer->isInvalid(), 'Patch must invalidate the feed indexer');

        $this->indexer->reindexList([$this->testCategoryId]);

        $activeRows = $this->connection->fetchAll(
            "SELECT * FROM {$this->resource->getTableName(self::FEED_TABLE)}
             WHERE source_entity_id = ? AND is_deleted = 0",
            [$this->testCategoryId]
        );
        $this->assertNotEmpty($activeRows, 'Reindex must rebuild active feed row under new identity');

        $deleteRows = $this->connection->fetchAll(
            "SELECT * FROM {$this->resource->getTableName(self::FEED_TABLE)}
             WHERE source_entity_id = ? AND is_deleted = 1",
            [$this->testCategoryId]
        );
        $this->assertEmpty($deleteRows, 'No is_deleted=1 rows must appear after patch + reindex');
    }

    /**
     * Idempotency: running patch twice must not fail or corrupt state.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testPatchIsIdempotent(): void
    {
        $this->patch->apply();
        $this->assertEmpty($this->fetchFeedRows(), 'First run must clear feed rows');
        $this->indexer->load(self::FEED_INDEXER);
        $this->assertTrue($this->indexer->isInvalid(), 'First run must invalidate the feed indexer');

        $this->patch->apply();
        $this->assertEmpty($this->fetchFeedRows(), 'Second run on empty table must be a no-op');
        $this->indexer->load(self::FEED_INDEXER);
        $this->assertTrue($this->indexer->isInvalid(), 'Second run must leave the indexer invalid');
    }

    /**
     * Active orphan row (category deleted before deploy, not yet synced) must be converted to a
     * tombstone with is_deleted=1 and status=RETRYABLE so SaaS receives the delete on next export.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testPatchConvertsActiveOrphanToTombstone(): void
    {
        $table = $this->resource->getTableName(self::FEED_TABLE);
        $row = $this->fetchFeedRows()[0] ?? [];
        $this->assertNotEmpty($row);

        // Simulate: category deleted from catalog but its feed row was never synced as is_deleted.
        $orphanEntityId = 999999;
        $this->connection->insert($table, [
            FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID          => hash('sha256', 'orphan-active-feed-id'),
            FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH        => $row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH],
            FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA        => $row[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA],
            FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED       => 0,
            FeedIndexMetadata::FEED_TABLE_FIELD_STATUS           => 200,
            FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID => $orphanEntityId,
            FeedIndexMetadata::FEED_TABLE_FIELD_MODIFIED_AT      =>
                $row[FeedIndexMetadata::FEED_TABLE_FIELD_MODIFIED_AT],
        ]);

        $this->patch->apply();

        $orphanRows = $this->connection->fetchAll(
            "SELECT * FROM $table WHERE source_entity_id = ?",
            [$orphanEntityId]
        );
        $this->assertCount(1, $orphanRows, 'Orphan row must be preserved (not deleted) by the patch');
        $this->assertEquals(
            1,
            (int)$orphanRows[0][FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED],
            'Active orphan must be converted to is_deleted=1'
        );
        $this->assertEquals(
            ExportStatusCodeProvider::RETRYABLE,
            (int)$orphanRows[0][FeedIndexMetadata::FEED_TABLE_FIELD_STATUS],
            'Status must be set to RETRYABLE so the delete is submitted on next export'
        );

        // Cleanup orphan row
        $this->connection->delete($table, ['source_entity_id = ?' => $orphanEntityId]);
    }

    private function fetchFeedRows(): array
    {
        return $this->connection->fetchAll(
            "SELECT * FROM {$this->resource->getTableName(self::FEED_TABLE)}
             WHERE source_entity_id = ?",
            [$this->testCategoryId]
        );
    }
}
