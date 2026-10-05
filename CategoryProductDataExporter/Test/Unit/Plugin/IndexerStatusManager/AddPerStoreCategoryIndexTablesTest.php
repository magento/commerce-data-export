<?php
/**
 * Copyright 2026 Adobe
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

namespace AdobeCommerce\CategoryProductDataExporter\Test\Unit\Plugin\IndexerStatusManager;

use AdobeCommerce\CategoryProductDataExporter\Plugin\IndexerStatusManager\AddPerStoreCategoryIndexTables;
use AdobeCommerce\IndexerStatusManager\Model\Config;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for AddPerStoreCategoryIndexTables.
 */
class AddPerStoreCategoryIndexTablesTest extends TestCase
{
    /**
     * The base, replica and per-store index tables are appended for a category-product indexer.
     */
    public function testAppendsPerStoreTablesForCategoryIndexers(): void
    {
        $plugin = new AddPerStoreCategoryIndexTables($this->storeManagerWithStoreIds(1, 2));

        $result = $plugin->afterGetIndexTables($this->createStub(Config::class), [], 'catalog_category_product');

        $this->assertEqualsCanonicalizing(
            [
                'catalog_category_product_index',
                'catalog_category_product_index_replica',
                'catalog_category_product_index_store1',
                'catalog_category_product_index_store1_replica',
                'catalog_category_product_index_store2',
                'catalog_category_product_index_store2_replica',
            ],
            $result
        );
    }

    /**
     * The same set is contributed for the reverse product-category indexer (shared index).
     */
    public function testAppendsPerStoreTablesForProductCategoryIndexer(): void
    {
        $plugin = new AddPerStoreCategoryIndexTables($this->storeManagerWithStoreIds(1));

        $result = $plugin->afterGetIndexTables($this->createStub(Config::class), [], 'catalog_product_category');

        $this->assertContains('catalog_category_product_index_store1', $result);
        $this->assertContains('catalog_category_product_index_store1_replica', $result);
    }

    /**
     * An unrelated indexer's table list is returned unchanged.
     */
    public function testLeavesOtherIndexersUntouched(): void
    {
        $plugin = new AddPerStoreCategoryIndexTables($this->storeManagerWithStoreIds(1, 2));

        $result = $plugin->afterGetIndexTables(
            $this->createStub(Config::class),
            ['some_other_table'],
            'catalog_product_price'
        );

        $this->assertSame(['some_other_table'], $result);
    }

    /**
     * Builds a StoreManager stub returning stores with the given ids.
     *
     * @param int ...$storeIds
     * @return StoreManagerInterface
     */
    private function storeManagerWithStoreIds(int ...$storeIds): StoreManagerInterface
    {
        $stores = [];
        foreach ($storeIds as $storeId) {
            $store = $this->createStub(Store::class);
            $store->method('getId')->willReturn($storeId);
            $stores[] = $store;
        }
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);

        return $storeManager;
    }
}
