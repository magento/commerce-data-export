<?php
/**
 * Copyright 2023 Adobe
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

namespace Magento\CatalogInventoryDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

class ProductLowStockTest extends AbstractProductTestHelper
{
    /**
     * Validate lowStock status
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogInventoryDataExporter::Test/_files/setup_is_low_stock.php
     * @magentoConfigFixture current_store cataloginventory/options/stock_threshold_qty 20
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testProductLowStock() : void
    {
        $sku = 'simple6';
        $storeViewCode = 'default';

        $productId = $this->getProductId($sku);

        $this->changeLowStockStatus($productId);

        $this->emulatePartialReindexBehavior([$productId]);
        $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
        $this->validateProductLowStock($extractedProduct);
    }

    /**
     * Change lowStock status of product
     *
     * @param int $productId
     * @return void
     * @throws \Exception
     */
    protected function changeLowStockStatus(int $productId) : void
    {
        /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
        $stockItem = Bootstrap::getObjectManager()->create(Item::class);
        $stockItem->load($productId, 'product_id');
        $stockItem->setQty(1);
        $stockItem->save();
    }

    /**
     * Validate lowStock status of product in extracted product data
     *
     * @param array $extractedProduct
     * @return void
     */
    protected function validateProductLowStock(array $extractedProduct) : void
    {
        $this->assertEquals(true, $extractedProduct['feedData']['lowStock']);
    }
}
