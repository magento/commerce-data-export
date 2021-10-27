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
use Magento\Framework\Exception\InputException;
use Magento\Indexer\Model\Indexer;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Model\SourceItemsProcessorInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class UnassignProductFromStockTest extends TestCase
{
    /**
     * @var FeedInterface
     */
    private $stockStatusFeed;

    /**
     * @var SourceItemsProcessorInterface
     */
    private $sourceItemProcessor;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->stockStatusFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('stock_statuses');
        $this->sourceItemProcessor = Bootstrap::getObjectManager()->get(SourceItemsProcessorInterface::class);
    }

    /**
     * @dataProvider stocksDataProvider
     * @param string $sku
     * @param array $sourcesToLeave
     * @param array $expectedData
     * @throws InputException
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     */
    public function testSourceItemStockUnassigned(string $sku, array $sourcesToLeave, array $expectedData)
    {
        $sourceItems = $this->getSourcesData($sku, $sourcesToLeave);
        $this->sourceItemProcessor->execute($sku, $sourceItems);

        $feedData = $this->getFeedData([$sku]);

        $this->verifyResults($feedData, $sku, $expectedData);
    }

    /**
     * @param array $skus
     * @return array[stock][sku]
     * @throws \Zend_Db_Statement_Exception
     */
    private function getFeedData(array $skus): array
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
     * @return array[]
     */
    public function stocksDataProvider(): array
    {
        return [
            'one_stock_unassign' => [
                'sku' => 'product_in_EU_stock_with_2_sources',
                'sources_to_leave' => ['eu-2'],
                'expected_data' => [
                    '10' => [
                        'sku' => 'product_in_EU_stock_with_2_sources',
                        'stock_id' => 10,
                        'deleted' => false
                    ],
                    '30' => [
                        'sku' => 'product_in_EU_stock_with_2_sources',
                        'stock_id' => 30,
                        'deleted' => true
                    ]
                ]
            ],
            'unassign_sources_from_multiple_stocks' => [
                'sku' => 'product_in_Global_stock_with_3_sources',
                'sources_to_leave' => ['eu-2'],
                'expected_data' => [
                    '10' => [
                        'sku' => 'product_in_Global_stock_with_3_sources',
                        'stock_id' => 10,
                        'deleted' => false
                    ],
                    '20' => [
                        'sku' => 'product_in_Global_stock_with_3_sources',
                        'stock_id' => 20,
                        'deleted' => true
                    ],
                    '30' => [
                        'sku' => 'product_in_Global_stock_with_3_sources',
                        'stock_id' => 30,
                        'deleted' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * @param string $sku
     * @param array $sourcesToUnassign
     * @return array
     */
    private function getSourcesData(string $sku, array $sourcesToUnassign): array
    {
        $sourcesData = [];
        foreach ($sourcesToUnassign as $sourceCode) {
            $sourcesData[] = [
                SourceItemInterface::SOURCE_CODE => $sourceCode,
                SourceItemInterface::SKU => $sku
            ];
        }

        return $sourcesData;
    }

    /**
     * @param array $feedData
     * @param string $sku
     * @param array $expectedData
     */
    private function verifyResults(array $feedData, string $sku, array $expectedData): void
    {
        foreach ($expectedData as $expectedStockId => $expectedStockData) {
            self::assertEquals(
                $expectedStockData,
                [
                    'sku' => $feedData[$expectedStockId][$sku]['sku'],
                    'stock_id' => $feedData[$expectedStockId][$sku]['stockId'],
                    'deleted' => $feedData[$expectedStockId][$sku]['deleted'],
                ]
            );
        }
    }
}
