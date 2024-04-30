<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Test\Integration;

use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class ExportStockStatusTest extends AbstractInventoryTestHelper
{
    /**
     * @var FeedInterface
     */
    private $stockStatusFeed;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->stockStatusFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('inventoryStockStatus');
    }

    /**
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     * @throws \Zend_Db_Statement_Exception
     * @throws NoSuchEntityException
     */
    public function testExportStockStatuses()
    {
        $productsSkus = [
            'product_in_EU_stock_with_2_sources',
            'product_in_Global_stock_with_3_sources',
            'product_with_default_stock_only',
            'product_in_default_and_2_EU_sources',
            'product_with_disabled_manage_stock',
            'product_with_enabled_backorders',
            'product_in_US_stock_with_disabled_source'
        ];
        $productIds = [];
        foreach ($productsSkus as $sku) {
            $productIds[$sku] = $this->getProductId($sku);
        }
        $actualStockStatuses = $this->getFeedData($productsSkus);

        foreach ($this->getExpectedStockStatus() as $stockId => $stockStatuses) {
            foreach ($stockStatuses as $sku => $stockStatus) {
                $stockStatus['productId'] = $productIds[$sku] ?? null;
                if (!isset($actualStockStatuses[$stockId][$sku])) {
                    self::fail("Cannot find stock status for stock $stockId & sku $sku");
                }
                $actualStockStatus = $actualStockStatuses[$stockId][$sku];
                // ignore fields for now
                unset(
                    $actualStockStatus['id'],
                    $actualStockStatus['lowStock'],
                    $actualStockStatus['updatedAt'],
                    $actualStockStatus['modifiedAt']
                );
                self::assertEquals(
                    $stockStatus,
                    $actualStockStatus,
                    "Wrong stock status for stock $stockId & sku $sku"
                );
            }
        }
    }

    /**
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources_and_reservations.php
     * @throws \Zend_Db_Statement_Exception
     * @throws NoSuchEntityException
     */
    public function testExportStockStatusesWithReservations()
    {
        $productsSkus = [
            'product_in_EU_stock_with_2_sources',
            'product_in_Global_stock_with_3_sources',
            'product_with_default_stock_only',
            'product_in_default_and_2_EU_sources',
            'product_with_disabled_manage_stock',
            'product_with_enabled_backorders',
            'product_in_US_stock_with_disabled_source'
        ];
        $productIds = [];
        foreach ($productsSkus as $sku) {
            $productIds[$sku] = $this->getProductId($sku);
        }
        $actualStockStatuses = $this->getFeedData($productsSkus);
        foreach ($this->getExpectedStockStatusForReservations() as $stockId => $stockStatuses) {
            foreach ($stockStatuses as $sku => $stockStatus) {
                $stockStatus['productId'] = $productIds[$sku] ?? null;
                if (!isset($actualStockStatuses[$stockId][$sku])) {
                    self::fail("Cannot find stock status for stock $stockId & sku $sku");
                }
                $actualStockStatus = $actualStockStatuses[$stockId][$sku];
                // ignore fields for now
                unset(
                    $actualStockStatus['id'],
                    $actualStockStatus['lowStock'],
                    $actualStockStatus['updatedAt'],
                    $actualStockStatus['modifiedAt']
                );
                self::assertEquals(
                    $stockStatus,
                    $actualStockStatus,
                    "Wrong stock status for stock $stockId & sku $sku"
                );
            }
        }
    }

    /**
     * @return \array[][]
     */
    private function getExpectedStockStatus(): array
    {
        return [
            // default stock
            '1' => [
                'product_with_default_stock_only' => [
                    'stockId' => '1',
                    'sku' => 'product_with_default_stock_only',
                    'qty' => 8.5,
                    'qtyForSale' => 8.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '1',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'qty' => 2,
                    'qtyForSale' => 2,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_with_disabled_manage_stock' => [
                    'stockId' => '1',
                    'sku' => 'product_with_disabled_manage_stock',
                    'qty' => 0,
                    'qtyForSale' => 0,
                    'infiniteStock' => true,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_with_enabled_backorders' => [
                    'stockId' => '1',
                    'sku' => 'product_with_enabled_backorders',
                    'qty' => 5,
                    'qtyForSale' => 5,
                    'infiniteStock' => true,
                    'isSalable' => true,
                    'deleted' => false,
                ],
            ],
            // EU Stock
            '10' => [
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'qty' => 9.5, // 5.5 (eu-1) + 4 (eu-2)
                    'qtyForSale' => 9.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'qty' => 3, // eu1 + eu2
                    'qtyForSale' => 3,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'qty' => 9.5,
                    'qtyForSale' => 9.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
            ],
            // US Stock
            '20' => [
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '20',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'qty' => 4, // us-1 source assigned to both stocks: US & Global
                    'qtyForSale' => 4,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_US_stock_with_disabled_source' => [
                    'stockId' => '20',
                    'sku' => 'product_in_US_stock_with_disabled_source',
                    'qty' => 0,
                    'qtyForSale' => 0,
                    'infiniteStock' => false,
                    'isSalable' => false,
                    'deleted' => false,
                ],
            ],
            // Global Stock
            '30' => [
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'qty' => 5, // 1 (eu-1) + 4 (us-1)
                    'qtyForSale' => 5,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'qty' => 5.5, // eu-1 only
                    'qtyForSale' => 5.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
            ],
        ];
    }

    /**
     * @return \array[][]
     */
    private function getExpectedStockStatusForReservations(): array
    {
        return [
            // default stock
            '1' => [
                'product_with_default_stock_only' => [
                    'stockId' => '1',
                    'sku' => 'product_with_default_stock_only',
                    'qty' => 8.5,
                    'qtyForSale' => 6.3,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '1',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'qty' => 2.0,
                    'qtyForSale' => 1,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_with_disabled_manage_stock' => [
                    'stockId' => '1',
                    'sku' => 'product_with_disabled_manage_stock',
                    'qty' => 0,
                    'qtyForSale' => 0,
                    'infiniteStock' => true,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_with_enabled_backorders' => [
                    'stockId' => '1',
                    'sku' => 'product_with_enabled_backorders',
                    'qty' => 5.0,
                    // Uncomment it after qtyForSale will be fixed
                    //'qtyForSale' => 0,
                    'qtyForSale' => -2.2, // JUST TEMPORARILY FIX. We should not allow negative values
                    'infiniteStock' => true,
                    'isSalable' => true,
                    'deleted' => false,
                ],
            ],
            // EU Stock
            '10' => [
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'qty' => 9.5, // 5.5 (eu-1) + 4 (eu-2)
                    'qtyForSale' => 0,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'qty' => 3, // eu1 + eu2
                    'qtyForSale' => 0,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'qty' => 9.5,
                    'qtyForSale' => 5.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
            ],
            // US Stock
            '20' => [
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '20',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'qty' => 4, // us-1 source assigned to both stocks: US & Global
                    'qtyForSale' => 2,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_US_stock_with_disabled_source' => [
                    'stockId' => '20',
                    'sku' => 'product_in_US_stock_with_disabled_source',
                    'qty' => 0,
                    'qtyForSale' => 0,
                    'infiniteStock' => false,
                    'isSalable' => false,
                    'deleted' => false,
                ],
            ],
            // Global Stock
            '30' => [
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'qty' => 5, // 1 (eu-1) + 4 (us-1)
                    'qtyForSale' => 2.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'qty' => 5.5, // eu-1 only
                    'qtyForSale' => 5.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
                    'deleted' => false,
                ],
            ],
        ];
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
}
