<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Fixture\PriceTypeAttributeProduct as PriceTypeAttributeProductFixture;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;

/**
 * Verifies that values of attributes with `price` frontend input are exported formatted as prices
 * (two decimals, no trailing zeros of the decimal DB storage format).
 *
 * @magentoAppArea adminhtml
 */
class ProductPriceTypeAttributeTest extends AbstractProductTestHelper
{
    #[DbIsolation(false)]
    #[AppIsolation(true)]
    #[DataFixture(PriceTypeAttributeProductFixture::class, ['price_attribute_value' => 12.5])]
    public function testPriceTypeAttributeValueIsFormattedAsPrice(): void
    {
        $extracted = $this->getExtractedProduct(PriceTypeAttributeProductFixture::SKU, 'default');
        $this->assertNotEmpty($extracted, 'No feed entry for the price-type attribute product.');

        $entry = null;
        foreach ($extracted['feedData']['attributes'] ?? [] as $attribute) {
            if (($attribute['attributeCode'] ?? null) === PriceTypeAttributeProductFixture::ATTRIBUTE_CODE) {
                $entry = $attribute;
                break;
            }
        }

        $this->assertNotNull($entry, 'Price-type attribute is missing in feedData.attributes.');
        $this->assertSame(['12.50'], $entry['value']);
    }

    #[DbIsolation(false)]
    #[AppIsolation(true)]
    #[DataFixture(PriceTypeAttributeProductFixture::class, ['price_attribute_value' => 12.339])]
    public function testPriceTypeAttributeValueIsFormattedAndRoundedAsPrice(): void
    {
        $extracted = $this->getExtractedProduct(PriceTypeAttributeProductFixture::SKU, 'default');
        $this->assertNotEmpty($extracted, 'No feed entry for the price-type attribute product.');

        $entry = null;
        foreach ($extracted['feedData']['attributes'] ?? [] as $attribute) {
            if (($attribute['attributeCode'] ?? null) === PriceTypeAttributeProductFixture::ATTRIBUTE_CODE) {
                $entry = $attribute;
                break;
            }
        }

        $this->assertNotNull($entry, 'Price-type attribute is missing in feedData.attributes.');
        $this->assertSame(['12.34'], $entry['value']);
    }
}
