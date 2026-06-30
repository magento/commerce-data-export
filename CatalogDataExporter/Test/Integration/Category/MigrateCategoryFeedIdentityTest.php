<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Category;

use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\CatalogDataExporter\Setup\Patch\Data\MigrateCategoryFeedIdentity;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
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

    private ObjectManagerInterface $objectManager;
    private ResourceConnection $resource;
    private AdapterInterface $connection;
    private MigrateCategoryFeedIdentity $patch;
    private int $testCategoryId;
    private ?int $orphanEntityId = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        // persistExportedFeed must be configured before any service depending on
        // CategoryFeedIndexMetadata is resolved, otherwise the singleton is already built.
        $this->objectManager->configure([
            'Magento\CatalogDataExporter\Model\Indexer\CategoryFeedIndexMetadata' => [
                'arguments' => ['persistExportedFeed' => true]
            ]
        ]);

        $this->resource = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
        $this->patch = $this->objectManager->get(MigrateCategoryFeedIdentity::class);
        $this->testCategoryId = (int)DataFixtureStorageManager::getStorage()->get('category')->getId();
        $this->loadFreshIndexer()->reindexList([$this->testCategoryId]);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->deleteFeedRows($this->testCategoryId);

        if ($this->orphanEntityId !== null) {
            $this->deleteFeedRows($this->orphanEntityId);
            $this->orphanEntityId = null;
        }
    }

    /**
     * Main scenario: patch clears feed rows for existing categories; reindex rebuilds with
     * correct identity and produces no is_deleted rows.
     */
    #[DbIsolation(false), AppIsolation(true), DataFixture(CategoryFixture::class, [], 'category')]
    public function testPatchClearsFeedRowsAndPreventsDeleteStormOnReindex(): void
    {
        $this->assertNotEmpty($this->fetchFeedRows($this->testCategoryId), 'Feed row must exist after partial reindex');

        $this->patch->apply();

        $this->assertEmpty(
            $this->fetchFeedRows($this->testCategoryId),
            'Patch must remove all feed rows for existing categories'
        );
        $this->assertIndexerIsInvalid('Patch must invalidate the feed indexer');

        $this->loadFreshIndexer()->reindexList([$this->testCategoryId]);

        $this->assertNotEmpty(
            $this->fetchFeedRows($this->testCategoryId, isDeleted: false),
            'Reindex must rebuild active feed row under new identity'
        );
        $this->assertEmpty(
            $this->fetchFeedRows($this->testCategoryId, isDeleted: true),
            'No is_deleted=1 rows must appear after patch + reindex'
        );
    }

    /**
     * Idempotency: running patch twice must not fail or corrupt state.
     */
    #[DbIsolation(false), AppIsolation(true), DataFixture(CategoryFixture::class, [], 'category')]
    public function testPatchIsIdempotent(): void
    {
        $this->patch->apply();
        $this->assertEmpty($this->fetchFeedRows($this->testCategoryId), 'First run must clear feed rows');
        $this->assertIndexerIsInvalid('First run must invalidate the feed indexer');

        $this->patch->apply();
        $this->assertEmpty($this->fetchFeedRows($this->testCategoryId), 'Second run on empty table must be a no-op');
        $this->assertIndexerIsInvalid('Second run must leave the indexer invalid');
    }

    /**
     * Active orphan row (category deleted before deploy, not yet synced) must be converted to a
     * tombstone with is_deleted=1 and status=RETRYABLE so SaaS receives the delete on next export.
     */
    #[DbIsolation(false), AppIsolation(true), DataFixture(CategoryFixture::class, [], 'category')]
    public function testPatchConvertsActiveOrphanToTombstone(): void
    {
        $this->orphanEntityId = 999999;
        $this->insertOrphanFeedRow($this->orphanEntityId);

        $this->patch->apply();

        $orphanRows = $this->fetchFeedRows($this->orphanEntityId);
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
    }

    /**
     * Inserts an active feed row for a non-existent entity to simulate a pre-deploy orphan.
     */
    private function insertOrphanFeedRow(int $entityId): void
    {
        $existingRow = $this->fetchFeedRows($this->testCategoryId)[0] ?? [];
        $this->assertNotEmpty($existingRow, 'Need an existing feed row to copy metadata from');

        $feedHash = $existingRow[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH];
        $feedData = $existingRow[FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA];
        $modifiedAt = $existingRow[FeedIndexMetadata::FEED_TABLE_FIELD_MODIFIED_AT];
        $this->connection->insert($this->resource->getTableName(self::FEED_TABLE), [
            FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID => hash('sha256', 'orphan-' . $entityId),
            FeedIndexMetadata::FEED_TABLE_FIELD_FEED_HASH => $feedHash,
            FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA => $feedData,
            FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED => 0,
            FeedIndexMetadata::FEED_TABLE_FIELD_STATUS => 200,
            FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID => $entityId,
            FeedIndexMetadata::FEED_TABLE_FIELD_MODIFIED_AT => $modifiedAt,
        ]);
    }

    /**
     * Asserts that the category feed indexer is in the invalid state.
     */
    private function assertIndexerIsInvalid(string $message): void
    {
        $this->assertTrue($this->loadFreshIndexer()->isInvalid(), $message);
    }

    /**
     * Indexer::load() does not clear the internal state cache, so a fresh instance
     * is required to read the actual DB value after invalidate() is called elsewhere.
     */
    private function loadFreshIndexer(): Indexer
    {
        return $this->objectManager->create(Indexer::class)->load(self::FEED_INDEXER);
    }

    /**
     * Returns feed rows for the given entity, optionally filtered by is_deleted flag.
     */
    private function fetchFeedRows(int $entityId, ?bool $isDeleted = null): array
    {
        $select = $this->connection->select()
            ->from($this->resource->getTableName(self::FEED_TABLE))
            ->where('source_entity_id = ?', $entityId);
        if ($isDeleted !== null) {
            $select->where('is_deleted = ?', (int)$isDeleted);
        }
        return $this->connection->fetchAll($select);
    }

    /**
     * Deletes all feed rows for the given entity from the feed table.
     */
    private function deleteFeedRows(int $entityId): void
    {
        $this->connection->delete(
            $this->resource->getTableName(self::FEED_TABLE),
            ['source_entity_id = ?' => $entityId]
        );
    }
}
