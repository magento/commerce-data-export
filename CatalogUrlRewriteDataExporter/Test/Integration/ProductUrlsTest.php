<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewriteDataExporter\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for product urls export
 */
class ProductUrlsTest extends AbstractProductTestHelper
{
    /**
     * A case where we have 2 store views but only one url rewrite in the default store view.
     * If there is no url in the current store view, then it should return the url from the default store view.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/CatalogUrlRewriteDataExporter/_files/setup_rewrites.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testGetProductUrlsFromDefaultStoreIfCurrentEmpty() : void
    {
        $this->markTestSkipped('DATA-2807: Magento\CatalogUrlRewriteDataExporter\Test\Integration\ProductUrlsTest');
        $skus = ['simple1', 'simple2', 'simple3'];
        $storeViewCodes = ['fixture_second_store'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Simple::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateUrlData($product, $extractedProduct);
            }
        }
    }

    /**
     * Validate product URL data
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/CatalogUrlRewriteDataExporter/_files/setup_rewrites.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testProductUrls() : void
    {
        $skus = ['simple1', 'simple2', 'simple3'];
        $storeViewCodes = ['default'];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setTypeInstance(Bootstrap::getObjectManager()->create(Simple::class));

            foreach ($storeViewCodes as $storeViewCode) {
                $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
                $this->validateUrlData($product, $extractedProduct);
            }
        }
    }

    /**
     * Return tech url if url rewrite empty
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/CatalogUrlRewriteDataExporter/_files/setup_rewrites.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testGetTechUrlIfUrlRewriteEmpty() : void
    {
        $sku = 'simple1';
        $storeViewCodes = ['fixture_second_store'];
        $product = $this->productRepository->get($sku);
        $UrlRewrite = Bootstrap::getObjectManager()->get(\Magento\UrlRewrite\Model\Storage\DbStorage::class);
        $UrlRewrite->deleteByData(['entity_id'=>10]);

        $product->setTypeInstance(Bootstrap::getObjectManager()->create(Simple::class));

        foreach ($storeViewCodes as $storeViewCode) {
            $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);
            $this->validateUrlData($product, $extractedProduct);
        }
    }

    /**
     * Validate URL data in extracted product product data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     */
    private function validateUrlData(ProductInterface $product, array $extractedProduct) : void
    {
        $canonicalUrl = $product->getUrlInStore();
        if ($product->getVisibility() > 1) {
            $this->assertStringContainsString($extractedProduct['feedData']['url'], $canonicalUrl);
        }
    }
}
