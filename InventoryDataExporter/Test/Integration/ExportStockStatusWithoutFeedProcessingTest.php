<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Test\Integration;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class ExportStockStatusWithoutFeedProcessingTest extends AbstractInventoryTestHelper
{
    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        Bootstrap::getObjectManager()->configure(
            [
                \Magento\InventoryDataExporter\Model\Indexer\StockStatusFeedIndexMetadata::class => [
                    'arguments' => [
                        'persistExportedFeed' => false
                    ]
                ]
            ]
        );
        parent::setUp();
    }

    /**
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     * @throws \Zend_Db_Statement_Exception
     * @throws NoSuchEntityException
     */
    public function testExportStockStatusesWithoutFeedPersisting()
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
        $this->emulatePartialReindexBehavior($productIds);
        $actualStockStatuses = $this->getFeedData($productsSkus);
        foreach ($this->getExpectedStockStatusMandatoryFeedsOnly() as $stockId => $stockStatuses) {
            foreach ($stockStatuses as $sku => $stockStatus) {
                $stockStatus['productId'] = $productIds[$sku] ?? null;
                if (!isset($actualStockStatuses[$stockId][$sku])) {
                    self::fail("Cannot find stock status for stock $stockId & sku $sku");
                }
                $actualStockStatus = $actualStockStatuses[$stockId][$sku];

                self::assertEquals(
                    $stockStatus,
                    $actualStockStatus,
                    "Wrong stock status for stock $stockId & sku $sku"
                );
            }
        }
    }

    /**
     * @magentoDataFixture Magento_InventoryDataExporter::Test/_files/products_with_sources.php
     * @magentoAppArea adminhtml
     * @throws \Zend_Db_Statement_Exception
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function testExportStockStatusesWithoutFeedPersistingProductsDeleted()
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
            $productId = $this->getProductId($sku);
            $productIds[$sku] = $productId;
            $this->productRepository->deleteById($sku);
        }
        $this->emulateCustomersBehaviorAfterDeleteAction();
        $this->emulatePartialReindexBehavior($productIds);
        $actualStockStatuses = $this->getFeedData($productsSkus);
        foreach ($this->getExpectedStockStatusMandatoryFeedsOnlyDeletedProducts() as $stockId => $stockStatuses) {
            foreach ($stockStatuses as $sku => $stockStatus) {
                $stockStatus['productId'] = $productIds[$sku] ?? null;
                if (!isset($actualStockStatuses[$stockId][$sku])) {
                    self::fail("Cannot find stock status for stock $stockId & sku $sku");
                }
                $actualStockStatus = $actualStockStatuses[$stockId][$sku];

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
    private function getExpectedStockStatusMandatoryFeedsOnly(): array
    {
        return [
            // default stock
            '1' => [
                'product_with_default_stock_only' => [
                    'stockId' => '1',
                    'sku' => 'product_with_default_stock_only',
                    'deleted' => false,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '1',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'deleted' => false,
                ],
                'product_with_disabled_manage_stock' => [
                    'stockId' => '1',
                    'sku' => 'product_with_disabled_manage_stock',
                    'deleted' => false,
                ],
                'product_with_enabled_backorders' => [
                    'stockId' => '1',
                    'sku' => 'product_with_enabled_backorders',
                    'deleted' => false,
                ],
            ],
            // EU Stock
            '10' => [
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'deleted' => false,
                ],
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'deleted' => false,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'deleted' => false,
                ],
            ],
            // US Stock
            '20' => [
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '20',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'deleted' => false,
                ],
                'product_in_US_stock_with_disabled_source' => [
                    'stockId' => '20',
                    'sku' => 'product_in_US_stock_with_disabled_source',
                    'deleted' => false,
                ],
            ],
            // Global Stock
            '30' => [
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'deleted' => false,
                ],
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'deleted' => false,
                ],
            ],
        ];
    }

    /**
     * @return \array[][]
     */
    private function getExpectedStockStatusMandatoryFeedsOnlyDeletedProducts(): array
    {
        return [
            // default stock
            '1' => [
                'product_with_default_stock_only' => [
                    'stockId' => '1',
                    'sku' => 'product_with_default_stock_only',
                    'deleted' => true,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '1',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'deleted' => true,
                ],
                'product_with_disabled_manage_stock' => [
                    'stockId' => '1',
                    'sku' => 'product_with_disabled_manage_stock',
                    'deleted' => true,
                ],
                'product_with_enabled_backorders' => [
                    'stockId' => '1',
                    'sku' => 'product_with_enabled_backorders',
                    'deleted' => true,
                ],
            ],
            // EU Stock
            '10' => [
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'deleted' => true,
                ],
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'deleted' => true,
                ],
                'product_in_default_and_2_EU_sources' => [
                    'stockId' => '10',
                    'sku' => 'product_in_default_and_2_EU_sources',
                    'deleted' => true,
                ],
            ],
            // US Stock
            '20' => [
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '20',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'deleted' => true,
                ],
                'product_in_US_stock_with_disabled_source' => [
                    'stockId' => '20',
                    'sku' => 'product_in_US_stock_with_disabled_source',
                    'deleted' => true,
                ],
            ],
            // Global Stock
            '30' => [
                'product_in_Global_stock_with_3_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_Global_stock_with_3_sources',
                    'deleted' => true,
                ],
                'product_in_EU_stock_with_2_sources' => [
                    'stockId' => '30',
                    'sku' => 'product_in_EU_stock_with_2_sources',
                    'deleted' => true,
                ],
            ],
        ];
    }
}
