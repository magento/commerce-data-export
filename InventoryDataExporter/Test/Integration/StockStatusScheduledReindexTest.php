<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Test\Integration;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class StockStatusScheduledReindexTest extends TestCase
{
    /**
     * feed indexer
     */
    private const STOCK_STATUS_FEED_INDEXER = 'inventory_data_exporter_stock_status';

    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemsFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->sourceItemsFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $indexer = Bootstrap::getObjectManager()->create(IndexerRegistry::class);
        $this->indexer = $indexer->get(self::STOCK_STATUS_FEED_INDEXER);
        $this->indexer->setScheduled(true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $changelog = $this->indexer->getView()->getChangelog();
        $currentVersion = $changelog->getVersion();
        $changelog->clear($currentVersion + 1);
        $this->indexer->setScheduled(false);
    }

    /**
     * Verify that change in the source_item will add updated SKU into stock status changelog table
     *
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/simple_product.php
     */
    public function testScheduledUpdate()
    {
        $sku = 'product_without_assigned_source';

        $currentVersion = $this->indexer->getView()->getChangelog()->getVersion();

        // check no product added to changelog yet to prevent false-positive result
        self::assertEmpty($this->indexer->getView()->getChangelog()->getList(0, $currentVersion + 1));

        $sourceItem = $this->sourceItemsFactory->create(['data' => [
            SourceItemInterface::SOURCE_CODE => 'default',
            SourceItemInterface::SKU => $sku,
            SourceItemInterface::QUANTITY => 1,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ]]);
        $this->sourceItemsSave->execute([$sourceItem]);

        $currentVersion = $this->indexer->getView()->getChangelog()->getVersion();

        // verify SKU is present in changelog
        self::assertEquals([$sku], $this->indexer->getView()->getChangelog()->getList(0, $currentVersion + 1));
    }
}
