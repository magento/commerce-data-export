<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Fixture\ConfigurableAndBundleProducts as ConfigurableAndBundleProductsFixture;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;

/**
 * Guards against numeric key collision in ProductOptions::get().
 */
class OptionsV2MixedProductTypesTest extends AbstractProductTestHelper
{
    #[DbIsolation(false)]
    #[AppIsolation(true)]
    #[DataFixture(ConfigurableAndBundleProductsFixture::class)]
    public function testBothProductTypesHaveOptionsV2WhenIndexedTogether(): void
    {
        $confId = $this->productRepository->get(ConfigurableAndBundleProductsFixture::CONFIGURABLE_SKU)->getId();
        $bundleId = (int)$this->productRepository->get(ConfigurableAndBundleProductsFixture::BUNDLE_SKU)->getId();
        $this->emulatePartialReindexBehavior([$confId, $bundleId]);

        $configurableData = $this->getExtractedProduct(
            ConfigurableAndBundleProductsFixture::CONFIGURABLE_SKU, 'default'
        );
        $bundleData = $this->getExtractedProduct(ConfigurableAndBundleProductsFixture::BUNDLE_SKU, 'default');
        self::assertNotEmpty(
            $configurableData['feedData']['optionsV2'] ?? [],
            'Configurable product optionsV2 must not be empty'
        );
        self::assertNotEmpty(
            $bundleData['feedData']['optionsV2'] ?? [],
            'Bundle product optionsV2 must not be empty'
        );
    }
}
