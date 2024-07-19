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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Product buyable flag test
 *
 * @magentoDataFixture Magento_CatalogInventoryDataExporter::Test/_files/setup_buyable_product.php
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class ProductBuyableTest extends AbstractProductTestHelper
{
    /**
     * Test constants
     */
    private const SKU = 'simple7';
    private const STORE_VIEW_CODE = 'default';

    /**
     * Validate buyable status of out of stock product
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testOutOfStockProduct() : void
    {
        $product = $this->productRepository->get(self::SKU);
        $productId = $product->getId();

        $this->setIsInStock((int)$productId, false);

        $this->emulatePartialReindexBehavior([$productId]);
        $this->validateProductBuyable($this->getExtractedProduct(self::SKU, self::STORE_VIEW_CODE));
    }

    /**
     * Validate buyable status of disabled product
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    public function testDisabledProduct() : void
    {
        $product = $this->productRepository->get(self::SKU);
        $productId = $product->getId();

        $this->disableProduct($product);

        $this->emulatePartialReindexBehavior([$productId]);
        $this->validateDisabledProduct($this->getExtractedProduct(self::SKU, self::STORE_VIEW_CODE));
    }

    /**
     * Validate buyable status of enabled product
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    public function testEnabledProduct() : void
    {
        $product = $this->productRepository->get(self::SKU);
        $productId = $product->getId();

        $this->enableProduct($product);
        $this->setIsInStock((int)$productId, true);

        $this->emulatePartialReindexBehavior([$productId]);
        $this->validateEnabledProduct($this->getExtractedProduct(self::SKU, self::STORE_VIEW_CODE));
    }

    /**
     * Set is in stock value of product
     *
     * @param int $productId
     * @param bool $isInStock
     * @return void
     * @throws \Exception
     */
    protected function setIsInStock(int $productId, bool $isInStock) : void
    {
        /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
        $stockItem = Bootstrap::getObjectManager()->create(Item::class);
        $stockItem->load($productId, 'product_id');
        $stockItem->setIsInStock($isInStock);
        $stockItem->save();
    }

    /**
     * Set product status to disabled
     *
     * @param ProductInterface $product
     *
     * @return void
     *
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    protected function disableProduct(ProductInterface $product) : void
    {
        $product->setStatus(Status::STATUS_DISABLED);
        $this->productRepository->save($product);
    }

    /**
     * Set product status to enabled
     *
     * @param ProductInterface $product
     *
     * @return void
     *
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    protected function enableProduct(ProductInterface $product) : void
    {
        $product->setStatus(Status::STATUS_ENABLED);
        $this->productRepository->save($product);
    }

    /**
     * Validate buyable status of product in extracted product data
     *
     * @param array $extractedProduct
     * @return void
     */
    protected function validateProductBuyable(array $extractedProduct) : void
    {
        $this->assertEquals(false, $extractedProduct['feedData']['buyable']);
    }

    /**
     * Validate buyable status of disabled product in extracted product data
     *
     * @param array $extractedProduct
     * @return void
     */
    protected function validateDisabledProduct(array $extractedProduct) : void
    {
        $this->assertEquals(false, $extractedProduct['feedData']['buyable']);
    }

    /**
     * Validate buyable status of enabled product in extracted product data
     *
     * @param array $extractedProduct
     * @return void
     */
    protected function validateEnabledProduct(array $extractedProduct) : void
    {
        $this->assertEquals(true, $extractedProduct['feedData']['buyable']);
    }
}
