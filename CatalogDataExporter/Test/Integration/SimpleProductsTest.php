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

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Catalog\Model\Product\Type\Simple;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for simple product export
 */
class SimpleProductsTest extends AbstractProductTestHelper
{
    /**
     * Validate simple product data
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products_with_media_gallery.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testSimpleProducts() : void
    {
        $skus = ['simple1', 'simple2', 'simple3'];
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($skus as $sku) {
            foreach ($storeViewCodes as $storeViewCode) {
                $store = $this->storeManager->getStore($storeViewCode);
                $product = $this->productRepository->get($sku, false, $store->getId());
                $product->setTypeInstance(Bootstrap::getObjectManager()->create(Simple::class));

                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateBaseProductData($product, $extractedProduct, $storeViewCode);
                $this->validateRealProductData($product, $extractedProduct);
                $this->validateCategoryData($product, $extractedProduct, $storeViewCode);
                $this->validatePricingData($extractedProduct);
                $this->validateImageUrls($product, $extractedProduct);
                $this->validateAttributeData($product, $extractedProduct);
                $this->validateMediaGallery($product, $extractedProduct);
                $this->validateVideoData($product, $extractedProduct);
                $this->validateImageData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate simple product data
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products_with_html_description.php
     *
     * @return void
     * @throws \Throwable
     */
    public function testSimpleProductWithHtmlDescription() : void
    {
        $sku = 'simple_html_description';
        $storeViewCodes = ['default', 'fixture_second_store'];

        foreach ($storeViewCodes as $storeViewCode) {
            $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
            self::assertEquals($sku, $extractedProduct['sku']);
            self::assertNotEmpty($extractedProduct['feedData']['description']);
        }
    }

    /**
     * Validate simple product data
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products_without_date.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testSimpleProductsWithoutCreatedAtModifiedAtAndUpdatedAt() : void
    {
        $sku = 'simple1';
        $storeViewCode = 'default';

        $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);

        $this->assertNotEmpty($extractedProduct['feedData']['createdAt']);
        $this->assertNotEmpty($extractedProduct['feedData']['updatedAt']);
        $this->assertNotEmpty($extractedProduct['feedData']['modifiedAt']);

        $this->assertNotEquals('0000-00-00 00:00:00', $extractedProduct['feedData']['createdAt']);
        $this->assertNotEquals('0000-00-00 00:00:00', $extractedProduct['feedData']['updatedAt']);
        $this->assertNotEquals('0000-00-00 00:00:00', $extractedProduct['feedData']['modifiedAt']);
    }
}
