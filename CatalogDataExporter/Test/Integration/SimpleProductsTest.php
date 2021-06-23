<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
     * @magentoDataFixture Magento/CatalogDataExporter/_files/setup_simple_products_with_media_gallery.php
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
                $this->validateCategoryData($product, $extractedProduct);
                $this->validatePricingData($product, $extractedProduct);
                $this->validateImageUrls($product, $extractedProduct);
                $this->validateAttributeData($product, $extractedProduct);
                $this->validateMediaGallery($product, $extractedProduct);
                $this->validateVideoData($product, $extractedProduct);
                $this->validateImageData($product, $extractedProduct);
            }
        }
    }
}
