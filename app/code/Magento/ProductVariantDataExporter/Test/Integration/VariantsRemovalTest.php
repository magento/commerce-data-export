<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Test\Integration;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;

/**
 * Test class for product variants export
 */
class VariantsRemovalTest extends AbstractProductVariantsTest
{
    /**
     * Test that variants are deleted as expected.
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     *
     * @return void
     */
    public function testDeleteConfigurableProductVariants(): void
    {
        try {
            /** @var \Magento\Catalog\Model\Product $product */
            $configurable = $this->productRepository->get('configurable');
            $configurableId = $configurable->getId();
            $extractedVariantsData = $this->productVariantsFeed->getFeedByProductIds(
                [$configurableId]
            )['feed'];
            $this->assertCount(2, $extractedVariantsData);

            $simple = $this->productRepository->get('simple_10');
            $this->deleteProduct($simple->getSku());
            $this->runIndexer([$configurableId]);

            $emptyVariantsData = $this->productVariantsFeed->getFeedByProductIds(
                [$configurableId]
            )['feed'];
            $this->assertCount(1, $emptyVariantsData);
            $deletedVariantsData = $this->productVariantsFeed->getDeletedByProductIds(
                [$configurableId]
            );
            $this->assertCount(1, $deletedVariantsData);
        } catch (NoSuchEntityException $e) {
            $this->fail('Expected product could not be retrieved');
        } catch (StateException $e) {
            $this->fail('Product could not be deleted');
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Delete product variant
     *
     * @param string $productSku
     * @throws StateException|NoSuchEntityException
     */
    private function deleteProduct($productSku) : void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        $this->productRepository->deleteById($productSku);

        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }
}
