<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Abstract Class AbstractInventoryTestHelper
 *
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractInventoryTestHelper extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Constants
     */
    protected const STOCK_STATUS_FEED_INDEXER = 'inventory_data_exporter_stock_status';

    /**
     * Inventory Stock Status Feed Table
     */
    private const STOCK_STATUS_FEED_TABLE = 'inventory_data_exporter_stock_status_feed';

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var FeedInterface
     */
    private $stockStatusFeed;

    public static function setUpBeforeClass(): void
    {
        Bootstrap::getObjectManager()->configure([
            'Magento\InventoryDataExporter\Model\Indexer\StockStatusFeedIndexMetadata' => [
                'arguments' => [
                    'persistExportedFeed' => true
                ]
            ]
        ]);
    }

    /**
     * Setup tests
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->resource = Bootstrap::getObjectManager()->create(ResourceConnection::class);
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->stockStatusFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('inventoryStockStatus');

        $this->indexer->load(self::STOCK_STATUS_FEED_INDEXER);
        $this->reindexStockStatusDataExporterTable();
    }

    /**
     * Reindex the full stock status data exporter table
     *
     * @return void
     * @throws \Throwable
     */
    private function reindexStockStatusDataExporterTable() : void
    {
        $this->indexer->reindexAll();
    }

    /**
     * Run partial indexer
     *
     * @param array $ids
     * @return void
     */
    protected function emulatePartialReindexBehavior(array $ids = []) : void
    {
        $this->indexer->reindexList($ids);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->truncateStockStatusDataExporterIndexTable();
    }

    /**
     * Truncates index table
     */
    private function truncateStockStatusDataExporterIndexTable(): void
    {
        $connection = $this->resource->getConnection();
        $feedTable = $this->resource->getTableName(self::STOCK_STATUS_FEED_TABLE);
        $connection->truncateTable($feedTable);

        $changeLogTable = $this->indexer->getView()->getChangelog()->getName();
        $connection->truncateTable($changeLogTable);
    }

    /**
     * @param string $sku
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getProductId(string $sku): ?int
    {
        $product = $this->productRepository->get($sku);
        return (int)$product->getId();
    }

    /**
     * @param array $skus
     * @return array[stock][sku]
     * @throws \Zend_Db_Statement_Exception
     */
    protected function getFeedData(array $skus): array
    {
        $output = [];
        foreach ($this->stockStatusFeed->getFeedSince('1')['feed'] as $item) {
            if (\in_array($item['sku'], $skus, true)) {
                $output[$item['stockId']][$item['sku']] = $item;
            }
        }
        return $output;
    }

    /**
     * Wait one second before test execution after fixtures created.
     *
     * @return void
     */
    protected function emulateCustomersBehaviorAfterDeleteAction(): void
    {
        // Avoid getFeed right after product was created.
        // We have to emulate real customers behavior
        // as it's possible that feed in test will be retrieved in same time as product created:
        // \Magento\DataExporter\Model\Query\RemovedEntitiesByModifiedAtQuery::getQuery
        sleep(1);
    }
}
