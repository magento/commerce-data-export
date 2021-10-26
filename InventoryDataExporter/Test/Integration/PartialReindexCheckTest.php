<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Indexer\Model\Indexer;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class PartialReindexCheckTest extends TestCase
{
    /**
     * feed indexer
     */
    private const STOCK_STATUS_FEED_INDEXER = 'inventory_data_exporter_stock_status';

    /**
     * @var Indexer
     */
    private $indexer;

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
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->stockStatusFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('stock_statuses');
        $this->sourceItemsFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     */
    public function testSourceItemQtyUpdated()
    {
        $sourceItem = $this->sourceItemsFactory->create(['data' => [
            SourceItemInterface::SOURCE_CODE => 'eu-2',
            SourceItemInterface::SKU => 'product_in_EU_stock_with_2_sources',
            SourceItemInterface::QUANTITY => 2,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ]]);
        $this->sourceItemsSave->execute([$sourceItem]);

        $sku = 'product_in_EU_stock_with_2_sources';
        $this->runIndexer([$sku]);
        $feedData = $this->getFeedData([$sku]);

        self::assertEquals(
            [
                'sku' => 'product_in_EU_stock_with_2_sources',
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
     * @param array $skus
     * @return array[stock][sku]
     */
    private function getFeedData(array $skus): array
    {
        $output = [];
        foreach ($this->stockStatusFeed->getFeedSince('1')['feed'] as $item) {
            if (in_array($item['sku'], $skus, true)) {
                $output[$item['stockId']][$item['sku']] = $item;
            }
        }
        return $output;
    }

    /**
     * @param string[] $skus
     * @return void
     *
     * @throws RuntimeException
     */
    private function runIndexer(array $skus): void
    {
        try {
            $this->indexer->load(self::STOCK_STATUS_FEED_INDEXER);
            $this->indexer->reindexList($skus);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not reindex stock status export index: ' . $e->getMessage());
        }
    }
}
