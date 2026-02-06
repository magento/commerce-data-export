<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Test\Integration\Model\Indexer;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Indexer\IndexStateProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test to reproduce the IndexStateProvider batch limit bug.
 *
 * This test verifies that when updates exceed the batch size, IndexStateProvider
 * correctly respects the batch limit and doesn't process all updates at once.
 *
 * The bug was in IndexStateProvider.php line 92:
 *   if (count($feedItems) === $this->feedItemsUpdates) // Wrong: comparing int to array
 * Should be:
 *   if (count($feedItems) === $this->batchSize) // Correct: comparing int to int
 */
#[AppIsolation(true)]
class IndexStateProviderBatchLimitTest extends TestCase
{
    private const TEST_BATCH_SIZE = 10;

    private $objectManager;
    private FeedIndexMetadata $metadata;
    private IndexStateProvider $indexStateProvider;
    private ResourceConnection $resourceConnection;
    private string $feedTableName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();

        // Create a simple custom metadata for testing with known batch size
        $this->metadata = $this->objectManager->create(
            FeedIndexMetadata::class,
            [
                'feedName' => 'test_feed',
                'sourceTableName' => 'test_source',
                'sourceTableField' => 'entity_id',
                'feedIdentity' => 'entity_id',
                'feedTableName' => 'test_feed_table',
                'feedTableField' => 'source_entity_id',
            ]
        );

        // Create the ORIGINAL IndexStateProvider (not the override) for testing
        // Use the fully qualified class name to get the parent class
        $this->indexStateProvider = $this->objectManager->create(
            \Magento\DataExporter\Model\Indexer\IndexStateProvider::class,
            [
                'metadata' => $this->metadata,
            ]
        );

        // Override batch size via reflection
        $reflection = new \ReflectionClass(\Magento\DataExporter\Model\Indexer\IndexStateProvider::class);
        $batchSizeProperty = $reflection->getProperty('batchSize');
        $batchSizeProperty->setValue($this->indexStateProvider, self::TEST_BATCH_SIZE);

        $this->resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $connection = $this->resourceConnection->getConnection();
        $this->feedTableName = $connection->getTableName($this->metadata->getFeedTableName());
    }

    /**
     * Test that addUpdates respects the batch limit
     *
     * The bug: when 15 updates are added with batch size 10,
     * IndexStateProvider should only return 10 items, not 15.
     */
    public function testAddUpdatesRespectsBatchLimit(): void
    {
        // Arrange: Create 15 update items (more than batch size of 10)
        $updates = [];
        for ($i = 1; $i <= 15; $i++) {
            $updates[] = [
                'feed_id' => "test-feed-id-{$i}",
                'source_entity_id' => $i,
            ];
        }

        // Act: Add all 15 updates to IndexStateProvider
        $this->indexStateProvider->addUpdates($updates);

        // Get the first batch of feed items (should be limited to batch size)
        $firstBatch = $this->indexStateProvider->getFeedItems();

        // Assert: First batch should respect the batch limit
        $this->assertLessThanOrEqual(
            self::TEST_BATCH_SIZE,
            count($firstBatch),
            'IndexStateProvider should respect batch limit and return max ' . self::TEST_BATCH_SIZE . ' items'
        );

        // Verify that we got exactly the batch size (since we have more than enough updates)
        $this->assertEquals(
            self::TEST_BATCH_SIZE,
            count($firstBatch),
            'First batch should return exactly batch size when more updates are available'
        );

        // Get the second batch to ensure all 15 items are eventually processed
        $secondBatch = $this->indexStateProvider->getFeedItems();

        // Assert: Second batch should contain the remaining 5 items
        $this->assertEquals(
            5,
            count($secondBatch),
            'Second batch should return the remaining 5 items'
        );

        // Verify all 15 items were processed across both batches
        $this->assertEquals(
            15,
            count($firstBatch) + count($secondBatch),
            'All 15 items should be processed across multiple batches'
        );
    }

    /**
     * Test that when update count equals batch size, all are processed
     */
    public function testExactlyBatchSizeUpdates(): void
    {
        // Arrange: Create exactly 10 updates (equals batch size)
        $updates = [];
        for ($i = 1; $i <= self::TEST_BATCH_SIZE; $i++) {
            $updates[] = [
                'feed_id' => "exact-feed-id-{$i}",
                'source_entity_id' => $i,
            ];
        }

        // Act
        $this->indexStateProvider->addUpdates($updates);
        $feedItems = $this->indexStateProvider->getFeedItems();

        // Assert: Should get all 10 items
        $this->assertEquals(
            self::TEST_BATCH_SIZE,
            count($feedItems),
            'IndexStateProvider should return all items when count equals batch size'
        );
    }

    /**
     * Test that when update count is less than batch size, all are processed
     */
    public function testFewerThanBatchSizeUpdates(): void
    {
        // Arrange: Create only 5 updates (less than batch size of 10)
        $updates = [];
        for ($i = 1; $i <= 5; $i++) {
            $updates[] = [
                'feed_id' => "few-feed-id-{$i}",
                'source_entity_id' => $i,
            ];
        }

        // Act
        $this->indexStateProvider->addUpdates($updates);
        $feedItems = $this->indexStateProvider->getFeedItems();

        // Assert: Should get all 5 items
        $this->assertEquals(
            5,
            count($feedItems),
            'IndexStateProvider should return all items when count is less than batch size'
        );
    }
}
