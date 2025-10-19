<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\DataExporter\Model\FeedPool;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Framework\Exception\NoSuchEntityException;
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
        $productId = (int)$this->productRepository->get($sku)->getId();
        $this->deleteProduct($sku);
        $output = $this->getExtractedProduct($sku, 'default');
        self::assertNotEmpty($output, "Empty feed received for sku: " . $sku);
        $this->validateProductRemoval($output, $productId);
    }

    /**
     * Validate product removal
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_product_removal.php
     *
     * @return void
     */
    public function testProductRecreate() : void
    {
        $sku = 'simple4';
        try {
            $product = $this->productRepository->get($sku, true, 0, true);
        } catch (NoSuchEntityException $e) {
            self::fail('Product with SKU ' . $sku . ' does not exist before test. Error: ' . $e->getMessage());
        }
        $this->deleteProduct($sku);
        // Recreate the product
        $product = $this->createProduct($product);
        $newProductId = (int)$product->getId();
        $output = $this->getExtractedProduct($sku, 'default');
        self::assertNotEmpty($output, "Empty feed received for sku: " . $sku);
        $this->validateProductCreated($output, $newProductId);
        // Delete the product again
        $this->deleteProduct($sku);
        $outputAfterDelete = $this->getExtractedProduct($sku, 'default');
        self::assertNotEmpty($outputAfterDelete, "Empty feed received for sku: " . $sku);
        $this->validateProductRemoval($outputAfterDelete, $newProductId);
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
     * Create product with prepared product model
     *
     * @param ProductInterface $product
     * @return ProductInterface
     */
    protected function createProduct(ProductInterface $product) : ProductInterface
    {
        $product->setId($product->getId() + 1);

        /** @var Set $attributeSet */
        $attributeSet = Bootstrap::getObjectManager()->create(Set::class);
        $attributeSet->load('SaaSCatalogAttributeSet', 'attribute_set_name');
        $product->setAttributeSetId($attributeSet->getId());
        try {
            $recreatedProduct = $this->productRepository->save($product);
        } catch (\Exception $e) {
            self::fail('Product creation failed: ' . $e->getMessage());
        }
        $this->emulatePartialReindexBehavior([$recreatedProduct->getId()]);

        return $recreatedProduct;
    }

    /**
     * Validate product removal
     *
     * @param array $extractedProduct
     * @param int $productId
     * @return void
     */
    protected function validateProductRemoval(array $extractedProduct, int $productId) : void
    {
        $this->assertEquals(true, $extractedProduct['feedData']['deleted']);
        $this->assertEquals($productId, $extractedProduct['source_entity_id']);
        $this->assertArrayHasKey('modifiedAt', $extractedProduct['feedData']);
    }

    /**
     * @param array $extractedProduct
     * @param int $productId
     * @return void
     */
    protected function validateProductCreated(array $extractedProduct, int $productId) : void
    {
        $this->assertEquals(false, $extractedProduct['feedData']['deleted']);
        $this->assertEquals($productId, $extractedProduct['source_entity_id']);
        $this->assertArrayHasKey('createdAt', $extractedProduct['feedData']);
        $this->assertArrayHasKey('modifiedAt', $extractedProduct['feedData']);
    }
}
