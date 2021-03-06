<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProductDataExporter\Model\Provider\Product\ProductVariants\ConfigurableId;
use RuntimeException;
use Throwable;

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
            $expected = $this->getExpectedProductVariants(['simple_10','simple_20']);

            $variantSimple10 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_10'
            ]);
            $variantSimple20 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_20'
            ]);
            $actual = $this->productVariantsFeed->getFeedByIds(
                [$variantSimple10, $variantSimple20]
            )['feed'];

            $diff = $this->arrayUtils->recursiveDiff($expected, $actual);
            self::assertEquals([], $diff, "Product variants response doesn't equal expected response");

        } catch (Throwable $e) {
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
            $variantSimple10 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_10'
            ]);
            $variantSimple20 = $this->idResolver->resolve([
                ConfigurableId::PARENT_SKU_KEY => 'configurable',
                ConfigurableId::CHILD_SKU_KEY => 'simple_20'
            ]);
            $realVariantsData = $this->productVariantsFeed->getFeedByIds(
                [$variantSimple10, $variantSimple20]
            )['feed'];
            $this->assertCount(2, $realVariantsData);

            $simple = $this->productRepository->get('simple_10'); //id10 and id20
            $this->deleteProduct($simple->getSku());
            $this->runIndexer([$configurableId]);

            $emptyVariantsData = $this->productVariantsFeed->getFeedByIds(
                [$variantSimple10, $variantSimple20]
            )['feed'];
            $this->assertCount(1, $emptyVariantsData); //id20
            $deletedVariantsData = $this->productVariantsFeed->getDeletedByIds(
                [$variantSimple10, $variantSimple20]
            );
            $this->assertCount(1, $deletedVariantsData); //id10
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Delete product variant
     *
     * @param string $productSku
     * @throws RuntimeException
     */
    private function deleteProduct(string $productSku): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        try {
            $this->productRepository->deleteById($productSku);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not delete product ' . $productSku);
        }

        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    /**
     * Get the expected variants for the first combination of options being tested.
     *
     * @param array $simples
     * @return array
     */
    private function getExpectedProductVariants(array $simples): array
    {
        $variants = [
            'simple_10' =>
                [
                    'id' => '8a880c29baa2ec8a5068350ec04f5b7d',
                    'optionValues' =>
                        [
                            [
                                'attributeCode' => 'test_configurable_first',
                                'label' => 'First Option 1',
//                                'valueIndex' => '107', //Skipped because they are unique
//                                'uid' => 'Y29uZmlndXJhYmxlLzIwOS8xMDc=', //Skipped because they are unique
                            ],
                            [
                                'attributeCode' => 'test_configurable_second',
                                'label' => 'Second Option 1',
                            ],
                        ],
                    'parentId' => '1',
                    'productId' => '10',
                    'parentSku' => 'configurable',
                    'productSku' => 'simple_10',
                    'deleted' => false,
                ],
            'simple_20' =>
                [
                    'id' => 'b91c35230afd24649f2ff60c79e7e7ba',
                    'optionValues' =>
                        [
                            [
                                'attributeCode' => 'test_configurable_first',
                                'label' => 'First Option 2',
                            ],
                            [
                                'attributeCode' => 'test_configurable_second',
                                'label' => 'Second Option 2',
                            ],
                        ],
                    'parentId' => '1',
                    'productId' => '20',
                    'parentSku' => 'configurable',
                    'productSku' => 'simple_20',
                    'deleted' => false,
                ],
        ];
        return array_values(array_intersect_key($variants, array_flip($simples)));
    }
}
