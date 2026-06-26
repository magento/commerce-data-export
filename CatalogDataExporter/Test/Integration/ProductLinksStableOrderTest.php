<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Fixture\ProductsWithRelatedLinks;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;

/**
 * Verifies that product links are returned in a stable, deterministic order (SKU ASC).
 *
 * The fixture creates link-product-zzz with a lower link_id (inserted first) and
 * link-product-aaa with a higher link_id. Without sorting, MySQL returns zzz first.
 * The test asserts aaa appears at index 0, which FAILS with current code and PASSES
 * once Links::get() sorts results by sku ASC before returning.
 */
class ProductLinksStableOrderTest extends AbstractProductTestHelper
{
    #[DbIsolation(false)]
    #[AppIsolation(true)]
    #[DataFixture(ProductsWithRelatedLinks::class)]
    public function testLinksAreSortedBySku(): void
    {
        $parentId = $this->getProductId(ProductsWithRelatedLinks::PARENT_SKU);
        $this->emulatePartialReindexBehavior([$parentId]);

        $extractedProduct = $this->getExtractedProduct(ProductsWithRelatedLinks::PARENT_SKU, 'default');
        $this->assertNotEmpty($extractedProduct, 'Extracted product data must not be empty');

        $links = $extractedProduct['feedData']['links'] ?? [];
        $this->assertNotEmpty($links, 'Product links must not be empty in feed data');
        $this->assertCount(2, $links, 'Expected exactly 2 related links');

        // Without ORDER BY in ProductLinksQuery, MySQL returns rows in link_id order:
        // link-product-zzz (lower link_id) appears at index 0 - this assertion fails.
        // After Links::get() sorts by sku ASC, link-product-aaa appears first.
        $this->assertSame(
            ProductsWithRelatedLinks::LINKED_SKU_SECOND,
            $links[0]['sku'],
            'First link must be link-product-aaa (alphabetically first).'
        );
        $this->assertSame(
            ProductsWithRelatedLinks::LINKED_SKU_FIRST,
            $links[1]['sku'],
            'Second link must be link-product-zzz (alphabetically last).'
        );
    }
}
