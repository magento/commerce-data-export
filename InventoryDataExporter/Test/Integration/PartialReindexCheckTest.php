<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Test\Integration;

use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class PartialReindexCheckTest extends AbstractInventoryTestHelper
{
    /**
     * @var FeedInterface
     */
    private $stockStatusFeed;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemsFactory;

    /**
     * @var BulkSourceUnassignInterface
     */
    private $bulkSourceUnassign;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stockStatusFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('inventoryStockStatus');
        $this->sourceItemsFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->bulkSourceUnassign = Bootstrap::getObjectManager()->get(BulkSourceUnassignInterface::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     */
    public function testSourceItemQtyUpdated()
    {
        $sku = 'product_in_EU_stock_with_2_sources';

        $sourceItem = $this->sourceItemsFactory->create(['data' => [
            SourceItemInterface::SOURCE_CODE => 'eu-2',
            SourceItemInterface::SKU => $sku,
            SourceItemInterface::QUANTITY => 2,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ]]);
        $this->sourceItemsSave->execute([$sourceItem]);

        $productId = $this->getProductId($sku);
        $this->emulatePartialReindexBehavior([$productId]);

        $feedData = $this->getFeedData([$sku]);

        self::assertEquals(
            [
                'sku' => $sku,
                'stock_id' => 10,
                'qty' => 7.5 // 5.5 (eu-1)  + 2 (changed for eu-2)
            ],
            [
                'sku' => $feedData[10][$sku]['sku'],
                'stock_id' => $feedData[10][$sku]['stockId'],
                'qty' => $feedData[10][$sku]['qty'],
            ]
        );
        // for Global Stock value remains the same
        self::assertEquals(
            [
                'sku' => 'product_in_EU_stock_with_2_sources',
                'stock_id' => 30,
                'qty' => 5.5 // 5.5 (eu-1)
            ],
            [
                'sku' => $feedData[30][$sku]['sku'],
                'stock_id' => $feedData[30][$sku]['stockId'],
                'qty' => $feedData[30][$sku]['qty'],
            ]
        );
    }

    /**
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     */
    public function testSourceBulkUnassign()
    {
        $skus = [
            'product_in_EU_stock_with_2_sources',
            'product_in_Global_stock_with_3_sources',
            'product_with_default_stock_only'
        ];

        $this->bulkSourceUnassign->execute(
            $skus,
            ['eu-1', 'default']
        );
        $productIds = [];
        foreach ($skus as $sku) {
            $productIds[] = $this->getProductId($sku);
        }
        $this->emulatePartialReindexBehavior($productIds);

        $feedData = $this->getFeedData($skus);

        $sku = 'product_with_default_stock_only';
        self::assertEquals(
            [
                'sku' => $sku,
                'stock_id' => 1,
                'qty' => 0, // no more sources left
                'isSalable' => false
            ],
            [
                'sku' => $feedData[1][$sku]['sku'],
                'stock_id' => $feedData[1][$sku]['stockId'],
                'qty' => $feedData[1][$sku]['qty'],
                'isSalable' => $feedData[1][$sku]['isSalable'],
            ]
        );
        $sku = 'product_in_EU_stock_with_2_sources';
        self::assertEquals(
            [
                'sku' => $sku,
                'stock_id' => 10,
                'qty' => 4, // only eu-2 left on stock 10
                'isSalable' => true
            ],
            [
                'sku' => $feedData[10][$sku]['sku'],
                'stock_id' => $feedData[10][$sku]['stockId'],
                'qty' => $feedData[10][$sku]['qty'],
                'isSalable' => $feedData[10][$sku]['isSalable'],
            ]
        );

        $sku = 'product_in_Global_stock_with_3_sources';
        self::assertEquals(
            [
                'sku' => $sku,
                'stock_id' => 10,
                'qty' => 2, // only eu-2 left on stock 10
                'isSalable' => true
            ],
            [
                'sku' => $feedData[10][$sku]['sku'],
                'stock_id' => $feedData[10][$sku]['stockId'],
                'qty' => $feedData[10][$sku]['qty'],
                'isSalable' => $feedData[10][$sku]['isSalable'],
            ]
        );

        $sku = 'product_in_Global_stock_with_3_sources';
        self::assertEquals(
            [
                'sku' => $sku,
                'stock_id' => 30,
                'qty' => 4, // only us-1 left on stock 30
                'isSalable' => true
            ],
            [
                'sku' => $feedData[30][$sku]['sku'],
                'stock_id' => $feedData[30][$sku]['stockId'],
                'qty' => $feedData[30][$sku]['qty'],
                'isSalable' => $feedData[30][$sku]['isSalable'],
            ]
        );
    }
}
