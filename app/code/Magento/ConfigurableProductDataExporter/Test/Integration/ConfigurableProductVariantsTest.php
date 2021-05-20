<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ProductVariantDataExporter\Test\Integration\AbstractProductVariantsTest;

/**
 * Test class for configurable product variants export
 */
class ConfigurableProductVariantsTest extends AbstractProductVariantsTest
{
    /**
     * Test configurable product variants.
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_products_with_two_attributes.php
     * @return void
     */
    public function testConfigurableVariants(): void
    {
        try {
            $configurable = $this->productRepository->get('configurable');
            $simples[] = $this->productRepository->get('simple_10');
            $simples[] = $this->productRepository->get('simple_20');
            $expected = $this->getExpectedProductVariants($configurable, $simples);

            $feed = $this->productVariantsFeed;
            $actual = $feed->getFeedByProductIds(
                [$configurable->getId()]
            )['feed'];

            $diff = $this->arrayUtils->recursiveDiff($expected, $actual);
            self::assertEquals([], $diff, "Product variants response doesn't equal expected response");

        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

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
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Delete product variant
     *
     * @param string $productSku
     * @throws \RuntimeException
     */
    private function deleteProduct(string $productSku): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        try {
            $this->productRepository->deleteById($productSku);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not delete product ' . $productSku);
        }

        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    /**
     * Get the expected variants for the first combination of options being tested.
     *
     * @param ProductInterface $configurable
     * @param Product[] $simples
     * @return array
     */
    private function getExpectedProductVariants(ProductInterface $configurable, array $simples): array
    {
        $configurableOptions = $configurable->getExtensionAttributes()->getConfigurableProductOptions();
        $variants = [];

        foreach ($simples as $simple) {
            $id = (\sprintf(
                'configurable/%1$s/%2$s',
                $configurable->getId(),
                $simple->getId(),
            ));
            $optionValues = [];
            foreach ($configurableOptions as $configurableOption) {
                $attributeCode = $configurableOption->getProductAttribute()->getAttributeCode();
                foreach ($configurableOption->getValues() as $configurableOptionValue) {
                    if ($simple->getData($attributeCode) === $configurableOptionValue->getValueIndex()) {
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        $optionUid = \base64_encode(\sprintf(
                            'configurable/%1$s/%2$s',
                            $configurableOption->getAttributeId(),
                            $configurableOptionValue->getValueIndex()
                        ));
                        $optionValues[] = \sprintf(
                            '%1$s:%2$s/%3$s',
                            $configurable->getId(),
                            $attributeCode,
                            $optionUid
                        );
                    }
                }
            }
            $variants[$id] = [
                'id' => $id,
                'parentId' => $configurable->getId(),
                'childId' => $simple->getId(),
                'optionValues' => $optionValues,
                'parentSku' => $configurable->getSku(),
                'productSku' => $simple->getSku(),
                'deleted' => false
            ];
        }

        return array_values($variants);
    }
}
