<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogDataExporter\Test\Integration\Stub\ControllableExportFeedStub;
use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Regression tests for the bug where a product with is_deleted=1 and status=RETRYABLE(2)
 * remains stuck after service recovery and resync.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResyncAfterServerErrorAndProductDeleteTest extends AbstractProductTestHelper
{
    private const STORE_VIEW_CODE = 'default';

    /**
     * @var \Magento\SaaSCommon\Cron\SubmitFeedInterface
     */
    private $resendFailedItemsCron;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    protected function setUp(): void
    {
        Bootstrap::getObjectManager()->configure([
            'preferences' => [
                ExportFeedInterface::class => ControllableExportFeedStub::class,
            ],
        ]);
        // Must be reset before parent::setUp() so the initial reindex uses status=200.
        ControllableExportFeedStub::reset();
        parent::setUp();

        $this->resendFailedItemsCron = Bootstrap::getObjectManager()->get(
            \Magento\SaaSCatalog\Cron\ProductSubmitFeed::class
        );
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->resourceConnection = Bootstrap::getObjectManager()->create(ResourceConnection::class);
    }

    protected function tearDown(): void
    {
        ControllableExportFeedStub::reset();
        parent::tearDown();
    }

    /**
     * A product deleted during a server outage must be successfully re-exported once the service recovers.
     *
     * End-to-end cron path: the "products_feed_resend_failed_items" cron job
     * (ProductSubmitFeed::execute) must re-export a stuck is_deleted=1/status=2 entry
     * after the service recovers.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture services_connector/services_connector_integration/production_api_key test_key
     * @magentoConfigFixture services_connector/services_connector_integration/production_private_key private_test_key
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     */
    public function testCronJobResendsDeletedProductAfterServiceRecovery(): void
    {
        $sku       = 'simple1';
        $productId = $this->getProductId($sku);

        // Precondition: product exported successfully during setUp().
        $this->assertFeedEntry($productId, 200, '0', 'Precondition: initial export must succeed');

        // --- Server goes down ---
        ControllableExportFeedStub::$shouldFail = true;

        // --- Product is deleted; immediate export fails → is_deleted=1, status=2 ---
        $this->deleteProduct($sku);

        $this->assertFeedEntry(
            $productId,
            ExportStatusCodeProvider::RETRYABLE,
            '1',
            'After server-down deletion: entry must be stuck at is_deleted=1, status=RETRYABLE(2)'
        );

        // --- Server recovers ---
        ControllableExportFeedStub::$shouldFail = false;

        // --- Cron job fires ---
        // ProductSubmitFeed::execute() (the "products_feed_resend_failed_items" cron job):
        $this->resendFailedItemsCron->execute();

        $this->assertFeedEntry(
            $productId,
            200,
            '1',
            'Cron job must re-export the stuck delete event; status must be 200 after service recovery'
        );
    }

    /**
     * Full resync path: a full reindex (indexer:reindex / saas:resync) must re-export a stuck
     * is_deleted=1/status=2 entry after service recovery.
     *
     * This is the same code path as `bin/magento indexer:reindex` or `saas:resync --feed=products`.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture services_connector/services_connector_integration/production_api_key test_key
     * @magentoConfigFixture services_connector/services_connector_integration/production_private_key private_test_key
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     */
    public function testFullReindexResendsDeletedProductAfterServiceRecovery(): void
    {
        $sku       = 'simple3';
        $productId = $this->getProductId($sku);

        // Precondition: product exported successfully during setUp().
        $this->assertFeedEntry($productId, 200, '0', 'Precondition: initial export must succeed');

        // --- Server goes down ---
        ControllableExportFeedStub::$shouldFail = true;

        // --- Product is deleted; immediate export fails → is_deleted=1, status=2 ---
        $this->deleteProduct($sku);

        $this->assertFeedEntry(
            $productId,
            ExportStatusCodeProvider::RETRYABLE,
            '1',
            'After server-down deletion: entry must be stuck at is_deleted=1, status=RETRYABLE(2)'
        );

        // --- Server recovers ---
        ControllableExportFeedStub::$shouldFail = false;

        $this->indexer->reindexAll();

        $this->assertFeedEntry(
            $productId,
            200,
            '1',
            'Full reindex must re-export the stuck delete event; status must be 200 after service recovery'
        );
    }

    /**
     * Safety guard: when a product is re-created with the same SKU (new entity_id) while a stuck
     * is_deleted=1/status=2 entry still exists, the re-creation must naturally heal the feed so
     * no delete event is sent to SaaS for the live SKU.
     *
     * How it works: feed_id = hash(sku, storeViewCode) — it is NOT tied to entity_id.
     * source_entity_id is a mutable column. When the new entity is indexed, insertOnDuplicate on
     * feed_id overwrites the stuck row in-place: the old is_deleted=1/status=2 entry is replaced
     * by is_deleted=0/status=200 with the new entity_id. The retry cron then finds nothing to retry,
     * so no delete event is ever dispatched to SaaS.
     *
     * Expected outcome after re-creation + resync:
     *   - New entity row (source_entity_id=newId):  is_deleted=0, status=200
     *   - Old entity row (source_entity_id=oldId):  not found — overwritten by new entity in-place
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture services_connector/services_connector_integration/production_api_key test_key
     * @magentoConfigFixture services_connector/services_connector_integration/production_private_key private_test_key
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_product_removal.php
     */
    public function testResyncDoesNotDeleteReCreatedProductWithSameSku(): void
    {
        $sku = 'simple4';
        // Load product object before deletion so we can re-create it afterwards.
        $originalProduct   = $this->productRepository->get($sku, true, 0, true);
        $originalProductId = (int)$originalProduct->getId();

        // Precondition: product exported successfully in setUp().
        $this->assertFeedEntry($originalProductId, 200, '0', 'Precondition: initial export must succeed');

        // --- Server goes down, product is deleted ---
        ControllableExportFeedStub::$shouldFail = true;
        $this->deleteProduct($sku);

        $this->assertFeedEntry(
            $originalProductId,
            ExportStatusCodeProvider::RETRYABLE,
            '1',
            'Old entity must be stuck at status=RETRYABLE(2) after server-down deletion'
        );

        // --- Server recovers, product is re-created with the SAME SKU (new entity_id) ---
        // Indexing the new entity calls insertOnDuplicate on the shared feed_id, which overwrites
        // the stuck is_deleted=1/status=2 row with the new entity's active data.
        ControllableExportFeedStub::$shouldFail = false;
        $newProduct   = $this->recreateProduct($originalProduct);
        $newProductId = (int)$newProduct->getId();

        $this->assertNotEquals(
            $originalProductId,
            $newProductId,
            'Re-created product must receive a different entity_id'
        );

        // --- Resync ---
        $this->emulatePartialReindexBehavior([$newProductId, $originalProductId]);

        // New entity must be active — the stuck delete row was replaced in-place on upsert.
        $this->assertFeedEntry(
            $newProductId,
            200,
            '0',
            'Re-created product must be exported as active; old stuck row must have been overwritten'
        );

        // Old entity_id must no longer appear as source_entity_id in the feed table.
        // The row was claimed by the new entity_id when insertOnDuplicate updated source_entity_id.
        $oldEntityRow = $this->getFeedEntry($originalProductId);
        $this->assertEmpty(
            $oldEntityRow,
            'Old entity row must be gone: source_entity_id was updated to the new entity_id on upsert'
        );
    }

    /**
     * Delete a product, wait one second (timestamp guard for DeletedEntitiesByModifiedAtQuery),
     * and trigger a partial reindex so is_deleted=1 is written to the feed table.
     */
    private function deleteProduct(string $sku): void
    {
        $registry = Bootstrap::getObjectManager()->get(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);

        try {
            $product   = $this->productRepository->get($sku);
            $productId = $product->getId();
            if ($productId) {
                $this->productRepository->delete($product);
                $this->emulateCustomersBehaviorAfterDeleteAction(); // sleep(1)
                $this->emulatePartialReindexBehavior([$productId]);
            }
        } catch (\Exception $e) {
            // Nothing to delete.
        } finally {
            $registry->unregister('isSecureArea');
            $registry->register('isSecureArea', false);
            $this->emulateCustomersBehaviorAfterDeleteAction();;
        }
    }

    /**
     * Re-create a previously deleted product with the same SKU.
     * Uses id+1 to guarantee a new entity_id (fixtures use fixed IDs with safe gaps).
     * The new entity is immediately reindexed and exported.
     */
    private function recreateProduct(ProductInterface $product): ProductInterface
    {
        $product->setId($product->getId() + 1);

        /** @var Set $attributeSet */
        $attributeSet = Bootstrap::getObjectManager()->create(Set::class);
        $attributeSet->load('SaaSCatalogAttributeSet', 'attribute_set_name');
        $product->setAttributeSetId($attributeSet->getId());

        try {
            $saved = $this->productRepository->save($product);
        } catch (\Exception $e) {
            self::fail('Product re-creation failed: ' . $e->getMessage());
        }

        $this->emulatePartialReindexBehavior([(int)$saved->getId()]);

        return $saved;
    }

    /**
     * Return the raw feed table row for the given entity and store view, or an empty array if not found.
     */
    private function getFeedEntry(int $entityId, string $storeViewCode = self::STORE_VIEW_CODE): array
    {
        $connection = $this->resourceConnection->getConnection();
        $feedTable  = $this->resourceConnection->getTableName(self::CATALOG_DATA_EXPORTER_TABLE);

        $select = $connection->select()
            ->from($feedTable, ['status', 'is_deleted', 'source_entity_id'])
            ->where('source_entity_id = ?', $entityId)
            ->where("json_extract(feed_data, '$.storeViewCode') = ?", $storeViewCode);

        return $connection->fetchRow($select) ?: [];
    }

    /**
     * Assert that a feed table row for the given entity (default store view) has the expected status and is_deleted.
     */
    private function assertFeedEntry(
        int    $entityId,
        int    $expectedStatus,
        string $expectedIsDeleted,
        string $message = ''
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $feedTable  = $this->resourceConnection->getTableName(self::CATALOG_DATA_EXPORTER_TABLE);

        $select = $connection->select()
            ->from($feedTable, ['status', 'is_deleted'])
            ->where('source_entity_id = ?', $entityId)
            ->where("json_extract(feed_data, '$.storeViewCode') = ?", self::STORE_VIEW_CODE);

        $row = $connection->fetchRow($select);

        $this->assertNotEmpty(
            $row,
            sprintf(
                '%sFeed entry not found for entity_id=%d store=%s',
                $message ? "$message — " : '',
                $entityId,
                self::STORE_VIEW_CODE
            )
        );
        $this->assertEquals(
            $expectedStatus,
            (int)$row['status'],
            sprintf('%sstatus mismatch for entity_id=%d', $message ? "$message — " : '', $entityId)
        );
        $this->assertEquals(
            $expectedIsDeleted,
            $row['is_deleted'],
            sprintf('%sis_deleted mismatch for entity_id=%d', $message ? "$message — " : '', $entityId)
        );
    }
}
