<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Category;

use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\CatalogDataExporter\Setup\Patch\Data\RestoreCategoryFeedV1Identity;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Test\Integration\DisablesFeedReadinessCheckers;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for RestoreCategoryFeedV1Identity data patch.
 *
 * Verifies that the patch clears feed rows for existing categories and invalidates
 * the indexer so a subsequent reindex rebuilds under categoryId identity.
 */
class RestoreCategoryFeedV1IdentityTest extends TestCase
{
    use DisablesFeedReadinessCheckers;

    private const FEED_TABLE = 'cde_categories_feed';
    private const FEED_INDEXER = 'catalog_data_exporter_categories';

    /**
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * @var RestoreCategoryFeedV1Identity
     */
    private RestoreCategoryFeedV1Identity $patch;

    /**
     * @var int
     */
    private int $testCategoryId;

    /**
     * @var int|null
     */
    private ?int $orphanEntityId = null;

    protected function setUp(): void
    {
        self::disableFeedReadinessCheckers();
        $this->objectManager = Bootstrap::getObjectManager();

        $this->objectManager->configure([
            'Magento\CatalogDataExporter\Model\Indexer\CategoryFeedIndexMetadata' => [
                'arguments' => ['persistExportedFeed' => true]
            ],
            'Magento\DataExporter\Model\Indexer\FeedIndexMetadata' => [
                'plugins' => [
                    'aco_data_exporter_category_feed_identity_override' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ]);

        $this->resource = $this->objectManager->get(ResourceConnection::class);
        $this->patch = $this->objectManager->get(RestoreCategoryFeedV1Identity::class);
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
     * Patch clears feed rows for existing categories and invalidates the indexer.
     */
    #[DbIsolation(false), AppIsolation(true), DataFixture(CategoryFixture::class, [], 'category')]
    public function testPatchClearsFeedRowsAndInvalidatesIndexer(): void
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
            'Reindex must rebuild active feed row under canonical identity'
        );
        $this->assertEmpty(
            $this->fetchFeedRows($this->testCategoryId, isDeleted: true),
            'Reindex must not produce old-identity deletion rows'
        );
    }

    /**
     * Repeated cleanup leaves the feed empty and the indexer invalid.
     */
    #[DbIsolation(false), AppIsolation(true), DataFixture(CategoryFixture::class, [], 'category')]
    public function testPatchIsIdempotent(): void
    {
        $this->patch->apply();
        $this->patch->apply();

        $this->assertEmpty($this->fetchFeedRows($this->testCategoryId));
        $this->assertIndexerIsInvalid('Repeated cleanup must leave the indexer invalid');
    }

    /**
     * Orphan active feed rows are converted to tombstones with retryable status.
     */
    #[DbIsolation(false), AppIsolation(true), DataFixture(CategoryFixture::class, [], 'category')]
    public function testPatchTombstonesOrphanRows(): void
    {
        $this->orphanEntityId = 999999;
        $this->insertOrphanFeedRow($this->orphanEntityId, isDeleted: false);

        $this->patch->apply();

        $orphanRows = $this->fetchFeedRows($this->orphanEntityId);
        $this->assertCount(1, $orphanRows, 'Orphan row must not be deleted from table');

        $orphanRow = $orphanRows[0];
        $this->assertSame('1', (string)$orphanRow['is_deleted'], 'Orphan row must be marked deleted');
        $this->assertSame(
            (string)ExportStatusCodeProvider::RETRYABLE,
            (string)$orphanRow['status'],
            'Orphan tombstone must have retryable status'
        );
    }

    /**
     * Fetch feed rows for a category.
     *
     * @param int $categoryId
     * @param bool|null $isDeleted
     * @return array
     */
    private function fetchFeedRows(int $categoryId, ?bool $isDeleted = null): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::FEED_TABLE))
            ->where(FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID . ' = ?', $categoryId);

        if ($isDeleted !== null) {
            $select->where(FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED . ' = ?', $isDeleted ? 1 : 0);
        }

        return $connection->fetchAll($select);
    }

    /**
     * Delete feed rows for a category.
     *
     * @param int $categoryId
     * @return void
     */
    private function deleteFeedRows(int $categoryId): void
    {
        $this->resource->getConnection()->delete(
            $this->resource->getTableName(self::FEED_TABLE),
            [FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID . ' = ?' => $categoryId]
        );
    }

    /**
     * Insert orphan feed row.
     *
     * @param int $entityId
     * @param bool $isDeleted
     * @return void
     */
    private function insertOrphanFeedRow(int $entityId, bool $isDeleted = false): void
    {
        $this->resource->getConnection()->insert(
            $this->resource->getTableName(self::FEED_TABLE),
            [
                'feed_id'                                             => 'orphan_test_' . $entityId,
                FeedIndexMetadata::FEED_TABLE_FIELD_SOURCE_ENTITY_ID => $entityId,
                FeedIndexMetadata::FEED_TABLE_FIELD_FEED_DATA        => '{}',
                FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED       => $isDeleted ? 1 : 0,
                FeedIndexMetadata::FEED_TABLE_FIELD_STATUS           => 200,
            ]
        );
    }

    /**
     * Assert indexer is invalid.
     *
     * @param string $message
     * @return void
     */
    private function assertIndexerIsInvalid(string $message): void
    {
        $indexer = $this->loadFreshIndexer();
        $this->assertTrue($indexer->isInvalid(), $message);
    }

    /**
     * Load fresh indexer instance.
     *
     * @return Indexer
     */
    private function loadFreshIndexer(): Indexer
    {
        return $this->objectManager->create(Indexer::class)->load(self::FEED_INDEXER);
    }
}
