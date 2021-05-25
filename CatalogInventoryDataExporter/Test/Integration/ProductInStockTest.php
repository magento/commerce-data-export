<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

class ProductInStockTest extends AbstractProductTestHelper
{
    /**
     * Validate inStock status
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/CatalogInventoryDataExporter/_files/setup_is_in_stock.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testProductInStock() : void
    {
        $sku = 'simple5';
        $storeViewCode = 'default';

        $this->changeInStockStatus($sku);

        $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
        $this->validateProductInStock($extractedProduct);
    }

    /**
     * Change inStock status of product
     *
     * @param string $sku
     * @return void
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    protected function changeInStockStatus(string $sku) : void
    {
        $product = $this->productRepository->get($sku);
        $productId = $product->getId();

        /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
        $stockItem = Bootstrap::getObjectManager()->create(Item::class);
        $stockItem->load($productId, 'product_id');
        $stockItem->setIsInStock(false);
        $stockItem->save();
    }

    /**
     * Validate inStock status of product in extracted product data
     *
     * @param array $extractedProduct
     * @return void
     */
    protected function validateProductInStock(array $extractedProduct) : void
    {
        $this->assertEquals(false, $extractedProduct['feedData']['inStock']);
    }
}
