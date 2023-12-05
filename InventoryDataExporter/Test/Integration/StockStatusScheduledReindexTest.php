<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Test\Integration;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class StockStatusScheduledReindexTest extends AbstractInventoryTestHelper
{
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
        parent::setUp();

        $this->sourceItemsFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
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

        // check the product is added to changelog due to reindex in the setUp
        self::assertNotEmpty($this->indexer->getView()->getChangelog()->getList(0, $currentVersion));

        $sourceItem = $this->sourceItemsFactory->create(['data' => [
            SourceItemInterface::SOURCE_CODE => 'default',
            SourceItemInterface::SKU => $sku,
            SourceItemInterface::QUANTITY => 1,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ]]);
        $this->sourceItemsSave->execute([$sourceItem]);

        $newVersion = $this->indexer->getView()->getChangelog()->getVersion();

        // verify SKU is present in changelog
        self::assertEquals([$sku], $this->indexer->getView()->getChangelog()->getList($currentVersion, $newVersion));
    }
}
