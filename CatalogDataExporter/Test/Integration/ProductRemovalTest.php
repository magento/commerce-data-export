<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\DataExporter\Model\FeedPool;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

class ProductRemovalTest extends AbstractProductTestHelper
{
    /**
     * @var \Magento\DataExporter\Model\FeedInterface
     */
    private $productFeed;

    /**
     * Integration test setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->productFeed = Bootstrap::getObjectManager()->get(FeedPool::class)->getFeed('products');
    }

    /**
     * Validate product removal
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_product_removal.php
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testProductRemoval() : void
    {
        $sku = 'simple4';
        $this->deleteProduct($sku);
        $output = $this->getExtractedProduct($sku, 'default');
        self::assertNotEmpty($output, "Empty feed received for sku: " . $sku);
        $this->validateProductRemoval($output['feedData']);
    }

    /**
     * Delete product from catalog_data_exporter_products
     *
     * @param string $sku
     * @return void
     */
    protected function deleteProduct(string $sku) : void
    {
        /** @var \Magento\Framework\Registry $registry */
        $registry = Bootstrap::getObjectManager()->get(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);

        try {
            $product = $this->productRepository->get($sku);
            $productId = $product->getId();
            if ($productId) {
                $this->productRepository->delete($product);
                $this->emulateCustomersBehaviorAfterDeleteAction();
                $this->emulatePartialReindexBehavior([$productId]);
            }
        } catch (\Exception $e) {
            //Nothing to delete
        }

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);
    }

    /**
     * Validate product removal
     *
     * @param array $extractedProduct
     * @return void
     */
    protected function validateProductRemoval(array $extractedProduct) : void
    {
        $this->assertEquals(true, $extractedProduct['deleted']);
        $this->assertArrayHasKey('modifiedAt', $extractedProduct);
    }
}
