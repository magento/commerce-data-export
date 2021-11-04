<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Test\Integration;

use Magento\DataExporter\Export\Processor;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class ExportStockStatusTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = Bootstrap::getObjectManager()->create(Processor::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     */
    public function testExportStockStatuses()
    {
        $actualStockStatus = $this->processor->process(
            'inventoryStockStatus',
            [
                ['sku' => 'product_in_EU_stock_with_2_sources'],
                ['sku' => 'product_in_Global_stock_with_3_sources'],
                ['sku' => 'product_with_default_stock_only'],
                ['sku' => 'product_in_default_and_2_EU_sources'],
                ['sku' => 'product_with_disabled_manage_stock'],
                ['sku' => 'product_with_enabled_backorders'],
                ['sku' => 'product_in_US_stock_with_disabled_source'],
            ]
        );

        $actualStockStatusFormatted = [];
        foreach ($actualStockStatus as $stockStatus) {
            $actualStockStatusFormatted[$stockStatus['stockId']][$stockStatus['sku']] = $stockStatus;
        }
        foreach ($this->getExpectedStockStatus() as $stockId => $stockStatuses) {
            foreach ($stockStatuses as $sku => $stockStatus) {
                if (!isset($actualStockStatusFormatted[$stockId][$sku])) {
                    self::fail("Cannot find stock status for stock $stockId & sku $sku");
                }
                $actualStockStatus = $actualStockStatusFormatted[$stockId][$sku];
                // ignore fields for now
                unset($actualStockStatus['id'], $actualStockStatus['lowStock'], $actualStockStatus['updatedAt']);
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
     */
    public function testExportStockStatusesWithReservations()
    {
        $actualStockStatus = $this->processor->process(
            'inventoryStockStatus',
            [
                ['sku' => 'product_in_EU_stock_with_2_sources'],
                ['sku' => 'product_in_Global_stock_with_3_sources'],
                ['sku' => 'product_with_default_stock_only'],
                ['sku' => 'product_in_default_and_2_EU_sources'],
                ['sku' => 'product_with_disabled_manage_stock'],
                ['sku' => 'product_with_enabled_backorders'],
                ['sku' => 'product_in_US_stock_with_disabled_source'],
            ]
        );

        $actualStockStatusFormatted = [];
        foreach ($actualStockStatus as $stockStatus) {
            $actualStockStatusFormatted[$stockStatus['stockId']][$stockStatus['sku']] = $stockStatus;
        }
        foreach ($this->getExpectedStockStatusForReservations() as $stockId => $stockStatuses) {
            foreach ($stockStatuses as $sku => $stockStatus) {
                if (!isset($actualStockStatusFormatted[$stockId][$sku])) {
                    self::fail("Cannot find stock status for stock $stockId & sku $sku");
                }
                $actualStockStatus = $actualStockStatusFormatted[$stockId][$sku];
                // ignore fields for now
                unset($actualStockStatus['id'], $actualStockStatus['lowStock'], $actualStockStatus['updatedAt']);
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
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '1',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'qty' => 2,
                    'qtyForSale' => 2,
                    'infiniteStock' => false,
                    'isSalable' => true,
                ],
                'product_with_disabled_manage_stock' => [
                    'stockId' => '1',
                    'sku' => 'product_with_disabled_manage_stock',
                    'qty' => 0,
                    'qtyForSale' => 0,
                    'infiniteStock' => true,
                    'isSalable' => true,
                ],
                'product_with_enabled_backorders' => [
                    'stockId' => '1',
                    'sku' => 'product_with_enabled_backorders',
                    'qty' => 5,
                    'qtyForSale' => 5,
                    'infiniteStock' => true,
                    'isSalable' => true,
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
                ],
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'qty' => 3, // eu1 + eu2
                    'qtyForSale' => 3,
                    'infiniteStock' => false,
                    'isSalable' => true,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'qty' => 9.5,
                    'qtyForSale' => 9.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
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
                ],
                'product_in_US_stock_with_disabled_source' => [
                    'stockId' => '20',
                    'sku' => 'product_in_US_stock_with_disabled_source',
                    'qty' => 0,
                    'qtyForSale' => 0,
                    'infiniteStock' => false,
                    'isSalable' => false,
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
                ],
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'qty' => 5.5, // eu-1 only
                    'qtyForSale' => 5.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
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
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '1',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'qty' => 2.0,
                    'qtyForSale' => 1,
                    'infiniteStock' => false,
                    'isSalable' => true,
                ],
                'product_with_disabled_manage_stock' => [
                    'stockId' => '1',
                    'sku' => 'product_with_disabled_manage_stock',
                    'qty' => 0,
                    'qtyForSale' => 0,
                    'infiniteStock' => true,
                    'isSalable' => true,
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
                ],
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'qty' => 3, // eu1 + eu2
                    'qtyForSale' => 0,
                    'infiniteStock' => false,
                    'isSalable' => true,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'qty' => 9.5,
                    'qtyForSale' => 5.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
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
                ],
                'product_in_US_stock_with_disabled_source' => [
                    'stockId' => '20',
                    'sku' => 'product_in_US_stock_with_disabled_source',
                    'qty' => 0,
                    'qtyForSale' => 0,
                    'infiniteStock' => false,
                    'isSalable' => false,
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
                ],
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'qty' => 5.5, // eu-1 only
                    'qtyForSale' => 5.5,
                    'infiniteStock' => false,
                    'isSalable' => true,
                ],
            ],
        ];
    }
}
