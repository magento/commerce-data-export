<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Test\Integration;

use Magento\Catalog\Model\Product;
use Magento\ProductVariantDataExporter\Test\Integration\AbstractProductVariantsTest;
use Magento\Framework\Exception\NoSuchEntityException;

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

        } catch (NoSuchEntityException $e) {
            $this->fail('Test products could not be retrieved');
        }
    }

    /**
     * Get the expected variants for the first combination of options being tested.
     *
     * @param Product $configurable
     * @param Product[] $simples
     * @return array
     */
    private function getExpectedProductVariants(Product $configurable, array $simples): array
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
                'option_values' => $optionValues,
                'parent_id' => $configurable->getId(),
                'deleted' => false
            ];
        }

        return array_values($variants);
    }
}
