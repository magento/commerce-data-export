<?php
/**
 * Copyright 2023 Adobe
 * All rights reserved
 *
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Test\Integration;

use Magento\Framework\App\ResourceConnection;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;
use function PHPUnit\Framework\assertEmpty;

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
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * Setup tests
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->resource = Bootstrap::getObjectManager()->create(ResourceConnection::class);
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);

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
        $feedTable = $this->resource->getTableName(self::STOCK_STATUS_FEED_INDEXER);
        $connection->truncateTable($feedTable);

        $changeLogTable = $this->indexer->getView()->getChangelog()->getName();
        $connection->truncateTable($changeLogTable);
    }
}
