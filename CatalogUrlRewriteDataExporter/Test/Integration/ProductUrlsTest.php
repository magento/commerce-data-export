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
     * Validate product URL data
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogUrlRewriteDataExporter::Test/Integration/_files/setup_rewrites.php
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function testProductUrls() : void
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
     * Validate URL data in extracted product product data
     *
     * @param ProductInterface $product
     * @param array $extractedProduct
     * @return void
     */
    private function validateUrlData(ProductInterface $product, array $extractedProduct) : void
    {
        $canonicalUrl = str_replace('index.php/', '', $product->getUrlInStore());
        $canonicalUrl = strtok($canonicalUrl, '?');
        if ($product->getVisibility() > 1) {
            $this->assertEquals($canonicalUrl, $extractedProduct['feedData']['url']);
        }
    }
}
