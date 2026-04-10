<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Fixture\CategoryWithoutAdminUrlPath as CategoryWithoutAdminUrlPathFixture;
use Magento\CatalogDataExporter\Test\Fixture\ProductEavAllBackendTypes as ProductEavAllBackendTypesFixture;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Indexer\Model\Processor;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Verifies that product and category EAV attributes are exported even when the admin store view
 * (store_id = 0) does not have a value for those attributes.
 */
class ExportWithoutAdminStoreValueTest extends AbstractProductTestHelper
{
    /**
     * @var Processor
     */
    private $indexerProcessor;

    protected function setUp(): void
    {
        $this->indexerProcessor = Bootstrap::getObjectManager()->create(Processor::class);
        parent::setUp();
    }

    /**
     * Verifies that product EAV attributes of every backend type (varchar, text, int, decimal,
     * datetime) are exported correctly when only a store-specific value exists and the admin store
     * (store_id = 0) has no value for those attributes.
     *
     * Attributes under test:
     *  - varchar   : meta_title        → feedData['metaTitle']
     *  - text      : short_description → feedData['shortDescription']
     *  - int       : status            → feedData['status']
     *  - decimal   : weight            → feedData['weight']
     *  - datetime  : news_from_date    → feedData['attributes']['news_from_date']
     *
     * @return void
     * @throws NoSuchEntityException
     */
    #[DbIsolation(false)]
    #[AppIsolation(true)]
    #[DataFixture(ProductEavAllBackendTypesFixture::class)]
    public function testAllEavBackendTypesExportedWhenAdminStoreValueMissing(): void
    {
        $sku = 'product_eav_types';
        $storeViewCode = 'fixture_second_store';

        $productId = $this->getProductId($sku);
        $this->emulatePartialReindexBehavior([$productId]);

        $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);

        $this->assertNotEmpty(
            $extractedProduct,
            "Product '$sku' must be present in the feed for store '$storeViewCode'."
        );

        $feedData = $extractedProduct['feedData'];

        // varchar: meta_title
        $this->assertEquals(
            'Store Meta Title',
            $feedData['metaTitle'],
            'varchar backend (meta_title): store-specific value must be exported when admin store has no value.'
        );

        // text: short_description
        $this->assertEquals(
            'Store Short Desc',
            $feedData['shortDescription'],
            'text backend (short_description): store-specific value must be exported when admin store has no value.'
        );

        // decimal: weight
        $this->assertEquals(
            2.5,
            $feedData['weight'],
            'decimal backend (weight): store-specific value must be exported when admin store has no value.'
        );

        // int: status (formatted by SystemEnumFormatter: 1 → "Enabled")
        $this->assertEquals(
            'Enabled',
            $feedData['status'],
            'int backend (status): store-specific value must be exported when admin store has no value.'
        );

        // Build a lookup map for feedData['attributes'] keyed by attributeCode
        $attributes = [];
        foreach ($feedData['attributes'] ?? [] as $attr) {
            $attributes[$attr['attributeCode']] = $attr;
        }

        // datetime: news_from_date
        $this->assertArrayHasKey(
            'news_from_date',
            $attributes,
            'datetime backend (news_from_date): attribute must appear in feed when admin store has no value.'
        );
        $this->assertEquals(
            ['2024-06-15 00:00:00'],
            $attributes['news_from_date']['value'],
            'datetime backend (news_from_date): store-specific value must be exported when admin store has no value.'
        );
    }

    /**
     * Product's categoryData must include the category path even when the category's url_path
     * attribute is absent from admin store (store_id = 0) but present in the store view.
     *
     * This simulates AC's behavior when a category is moved: AC removes the url_path from admin
     * store but keeps store-specific url_path values.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    #[DbIsolation(false)]
    #[AppIsolation(true)]
    #[DataFixture(CategoryWithoutAdminUrlPathFixture::class)]
    public function testProductCategoryDataExportedWhenCategoryHasNoAdminUrlPath(): void
    {
        $sku = 'product_cat_no_admin_url';
        $storeViewCode = 'fixture_second_store';

        // Ensure category_product index and dependent indexes are up to date
        $this->indexerProcessor->updateMview();
        $this->indexerProcessor->reindexAllInvalid();

        $productId = $this->getProductId($sku);
        $this->emulatePartialReindexBehavior([$productId]);

        $extractedProduct = $this->getExtractedProduct($sku, $storeViewCode);

        $this->assertNotEmpty(
            $extractedProduct,
            "Product '$sku' must be present in the feed for store '$storeViewCode'."
        );

        $categoryData = $extractedProduct['feedData']['categoryData'] ?? [];
        $this->assertNotEmpty(
            $categoryData,
            "Product categoryData must not be empty even when the category's url_path is "
            . "absent from admin store (0) but present in the store view."
        );

        $categoryEntry = null;
        foreach ($categoryData as $entry) {
            if ((string)$entry['categoryId'] === '701') {
                $categoryEntry = $entry;
                break;
            }
        }

        $this->assertNotNull(
            $categoryEntry,
            "Category 701 must appear in the product's categoryData even without an admin url_path."
        );
        $this->assertEquals(
            'cat-no-admin-url-701',
            $categoryEntry['categoryPath'],
            "categoryPath must use the store-specific url_path when admin store has no value."
        );
    }
}
