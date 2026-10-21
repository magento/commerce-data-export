<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Test\Integration;

use Magento\Catalog\Model\Product;
use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\DataExporter\Export\Processor;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav as EavModifier;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Indexer\Model\Indexer;
use AdobeCommerce\ProductReviewSummaryAttributes\Api\ReviewSummarySourceInterface;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract;
use AdobeCommerce\ProductReviewSummaryAttributes\Plugin\AddReviewAttributesToProductFeed;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Review summary attributes creation and export
 */
class ReviewSummaryAttributesTest extends AbstractProductTestHelper
{
    private const SKU = 'simple1';
    private const STAGING_EAV_MODIFIER = 'Magento\\CatalogStaging\\Ui\\DataProvider\\Product\\Form\\Modifier\\Eav';
    private const PRODUCT_ATTRIBUTES_FEED_INDEXER = 'catalog_data_exporter_product_attributes';
    private const PRODUCT_ATTRIBUTES_FEED_TABLE = 'cde_product_attributes_feed';

    /**
     * @var Processor
     */
    private Processor $processor;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->deleteReviewAttributes();
        parent::setUp();
        $this->processor = Bootstrap::getObjectManager()->get(Processor::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->deleteReviewAttributes();
        parent::tearDown();
    }

    /**
     * Default (disabled) source creates no attributes and does not change the products feed
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     *
     * @return void
     */
    public function testNothingIsExportedWithDefaultSource(): void
    {
        foreach ($this->getProductFeedItems() as $product) {
            $this->assertSame([], $this->getReviewAttributesInFeed($product));
        }
        $this->assertSame([], $this->getReviewAttributes());
    }

    /**
     * Summaries without approved reviews do not create attributes
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     *
     * @return void
     */
    public function testAttributesAreNotCreatedWithoutApprovedReviews(): void
    {
        $productId = $this->getProductId(self::SKU);
        $key = (new ReviewAttributeContract())->key($productId, 'default');
        $plugin = $this->createPlugin([$key => ['reviewCount' => 0, 'ratingSummary' => 0.0]]);

        $products = $this->getProductFeedItems();
        $result = $plugin->afterProcess($this->processor, $products, 'products');

        $this->assertSame($products, $result);
        $this->assertSame([], $this->getReviewAttributes());
    }

    /**
     * Approved reviews create the attributes and export values for products with a summary only
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     *
     * @return void
     */
    public function testAttributesAreCreatedAndExported(): void
    {
        $productId = $this->getProductId(self::SKU);
        $key = (new ReviewAttributeContract())->key($productId, 'default');
        $plugin = $this->createPlugin([$key => ['reviewCount' => 2, 'ratingSummary' => 90.0]]);

        $products = $this->getProductFeedItems();
        $result = $plugin->afterProcess($this->processor, $products, 'products');

        $attributes = $this->getReviewAttributes();
        $this->assertCount(2, $attributes);
        foreach ($attributes as $attribute) {
            $this->assertSame('numeric', $attribute['frontend_input']);
            $this->assertSame('decimal', $attribute['backend_type']);
            $this->assertSame('0', $attribute['is_user_defined']);
            $this->assertSame('1', $attribute['is_visible']);
            $this->assertSame('0', $attribute['is_visible_on_front']);
            $this->assertSame('0', $attribute['used_in_product_listing']);
            $this->assertSame('0', $attribute['used_for_sort_by']);
            $this->assertSame('0', $attribute['is_filterable_in_search']);
            $this->assertSame('0', $attribute['is_searchable']);
            $this->assertSame('0', $attribute['is_filterable']);
        }

        $exported = [];
        foreach ($result as $product) {
            $exported[$product['storeViewCode']] = $this->getReviewAttributesInFeed($product);
        }
        $this->assertSame(
            [
                ReviewAttributeContract::ATTRIBUTE_REVIEW_COUNT => ['2'],
                ReviewAttributeContract::ATTRIBUTE_RATING_SUMMARY => ['90'],
            ],
            $exported['default']
        );
        $this->assertSame([], $exported['fixture_second_store']);

        $this->assertProductAttributesFeedMetadata(array_column($attributes, 'attribute_id'));
    }

    /**
     * The attributes are removed from the product edit form, including the staging update form when available
     *
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     *
     * @return void
     */
    public function testAttributesAreRemovedFromProductForm(): void
    {
        $modifierClasses = [EavModifier::class];
        if (class_exists(self::STAGING_EAV_MODIFIER)) {
            $modifierClasses[] = self::STAGING_EAV_MODIFIER;
        }

        foreach ($modifierClasses as $modifierClass) {
            $modifier = Bootstrap::getObjectManager()->create($modifierClass);
            $attributesToEliminate = (new \ReflectionProperty(EavModifier::class, 'attributesToEliminate'))
                ->getValue($modifier);
            foreach (ReviewAttributeContract::ATTRIBUTE_CODES as $attributeCode) {
                $this->assertContains($attributeCode, $attributesToEliminate, $modifierClass);
            }
        }
    }

    /**
     * Validate product attributes feed metadata of the review summary attributes
     *
     * @param array $attributeIds
     * @return void
     */
    private function assertProductAttributesFeedMetadata(array $attributeIds): void
    {
        Bootstrap::getObjectManager()->get(EavConfig::class)->clear();
        $indexer = Bootstrap::getObjectManager()->create(Indexer::class);
        $indexer->load(self::PRODUCT_ATTRIBUTES_FEED_INDEXER);
        $indexer->reindexList($attributeIds);

        $exportedCodes = [];
        foreach ($this->getProductAttributesFeedItems() as $attribute) {
            $exportedCodes[$attribute['attributeCode']] = true;
            $this->assertTrue($attribute['numeric']);
            $this->assertTrue($attribute['visible']);
            $this->assertFalse($attribute['visibleInListing']);
            $this->assertFalse($attribute['visibleInSearch']);
            $this->assertFalse($attribute['sortable']);
            $this->assertFalse($attribute['filterableInSearch']);
        }
        $this->assertEqualsCanonicalizing(ReviewAttributeContract::ATTRIBUTE_CODES, array_keys($exportedCodes));
    }

    /**
     * Create the feed plugin with an enabled review summary source
     *
     * @param array $summaries
     * @return AddReviewAttributesToProductFeed
     */
    private function createPlugin(array $summaries): AddReviewAttributesToProductFeed
    {
        $source = new class($summaries) implements ReviewSummarySourceInterface {
            /**
             * @param array $summaries
             */
            public function __construct(private readonly array $summaries)
            {
            }

            /**
             * @inheritdoc
             */
            public function isEnabled(): bool
            {
                return true;
            }

            /**
             * @inheritdoc
             */
            public function getSummaries(array $productIds, array $storeViewCodes): array
            {
                return $this->summaries;
            }
        };

        return Bootstrap::getObjectManager()->create(
            AddReviewAttributesToProductFeed::class,
            ['reviewSummarySource' => $source]
        );
    }

    /**
     * Get review summary attributes from a products feed item
     *
     * @param array $product
     * @return array
     */
    private function getReviewAttributesInFeed(array $product): array
    {
        $attributes = [];
        foreach ($product['attributes'] ?? [] as $attribute) {
            if (in_array($attribute['attributeCode'], ReviewAttributeContract::ATTRIBUTE_CODES, true)) {
                $attributes[$attribute['attributeCode']] = $attribute['value'];
            }
        }

        return $attributes;
    }

    /**
     * Get exported product attributes feed items of the review summary attributes
     *
     * @return array
     */
    private function getProductAttributesFeedItems(): array
    {
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $select = $connection->select()
            ->from($resource->getTableName(self::PRODUCT_ATTRIBUTES_FEED_TABLE), ['feed_data'])
            ->where(
                'json_unquote(json_extract(feed_data, \'$.attributeCode\')) IN (?)',
                ReviewAttributeContract::ATTRIBUTE_CODES
            )
            ->where('is_deleted = ?', 0);

        return array_map(
            static fn (string $feedData) => json_decode($feedData, true),
            $connection->fetchCol($select)
        );
    }

    /**
     * Get review summary attributes from the EAV storage
     *
     * @return array
     */
    private function getReviewAttributes(): array
    {
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $select = $connection->select()
            ->from(['ea' => $resource->getTableName('eav_attribute')])
            ->join(
                ['cea' => $resource->getTableName('catalog_eav_attribute')],
                'cea.attribute_id = ea.attribute_id'
            )
            ->where('ea.attribute_code IN (?)', ReviewAttributeContract::ATTRIBUTE_CODES)
            ->where('ea.entity_type_id = ?', $this->getProductEntityTypeId());

        return $connection->fetchAll($select);
    }

    /**
     * Delete review summary attributes created by the test
     *
     * @return void
     */
    private function deleteReviewAttributes(): void
    {
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $resource->getConnection()->delete(
            $resource->getTableName('eav_attribute'),
            [
                'attribute_code IN (?)' => ReviewAttributeContract::ATTRIBUTE_CODES,
                'entity_type_id = ?' => $this->getProductEntityTypeId(),
            ]
        );
        Bootstrap::getObjectManager()->get(EavConfig::class)->clear();
    }

    /**
     * Get exported products feed items of the test product for all store views
     *
     * @return array
     */
    private function getProductFeedItems(): array
    {
        $products = [];
        foreach (['default', 'fixture_second_store'] as $storeViewCode) {
            $extractedProduct = $this->getExtractedProduct(self::SKU, $storeViewCode);
            $this->assertNotEmpty($extractedProduct['feedData'] ?? null);
            $products[] = $extractedProduct['feedData'];
        }

        return $products;
    }

    /**
     * @return int
     */
    private function getProductEntityTypeId(): int
    {
        return (int)Bootstrap::getObjectManager()->get(EavConfig::class)->getEntityType(Product::ENTITY)->getId();
    }
}
