<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Test\Integration;

use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewSummaryAttributeRegistry;
use AdobeCommerce\ProductReviewSummarySync\Observer\ResyncOnReviewActiveChange;
use Magento\Catalog\Model\Product;
use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\DataExporter\Export\Processor;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Registry;
use Magento\Review\Model\Rating;
use Magento\Review\Model\ResourceModel\Rating\Option\Collection as RatingOptionCollection;
use Magento\Review\Model\ResourceModel\Review\Collection as ReviewCollection;
use Magento\Review\Model\Review;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Native review summaries export and sync with the products feed
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReviewSummarySyncTest extends AbstractProductTestHelper
{
    private const SKU = 'simple1';
    private const SECOND_STORE = 'fixture_second_store';
    private const RATING_CODE = 'Quality';
    private const REVIEW_ACTIVE = 'catalog/review/active';
    private const PRODUCTS_FEED_METADATA = 'Magento\CatalogDataExporter\Model\Indexer\ProductFeedIndexMetadata';

    /**
     * @var Processor
     */
    private Processor $processor;

    /**
     * @var IndexerInterface
     */
    private IndexerInterface $productsFeedIndexer;

    /**
     * @var array|null rating stores and status to restore
     */
    private ?array $ratingState = null;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->deleteReviewAttributes();
        $this->saveReviewActive('1');
        parent::setUp();
        $this->processor = Bootstrap::getObjectManager()->get(Processor::class);
        $this->productsFeedIndexer = Bootstrap::getObjectManager()->get(IndexerRegistry::class)
            ->get(self::CATALOG_DATA_EXPORTER);
        $this->productsFeedIndexer->setScheduled(true);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->deleteReviews();
        $this->deleteReviewSummaries();
        $this->restoreRating();
        $this->deleteReviewActiveConfig();
        $this->deleteReviewAttributes();
        parent::tearDown();
    }

    /**
     * Approved reviews are exported per store view, store views without reviews are exported with 0
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     *
     * @return void
     */
    public function testApprovedReviewsAreExported(): void
    {
        $productId = $this->getProductId(self::SKU);
        $this->createReview($productId, 5);
        $this->createReview($productId, 4);
        $this->createReview($productId, 1, Review::STATUS_PENDING);

        $this->assertSame(
            [
                'default' => $this->expectedValues('2', '90'),
                self::SECOND_STORE => $this->expectedValues('0', '0'),
            ],
            $this->exportReviewAttributes($productId)
        );
    }

    /**
     * Review status changes and deletion schedule the product and update the exported values
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     *
     * @return void
     */
    public function testReviewChangesScheduleProductUpdate(): void
    {
        $productId = $this->getProductId(self::SKU);
        $review = $this->createReview($productId, 5);
        $this->assertContains($productId, $this->getScheduledProductIds());
        $this->assertSame($this->expectedValues('1', '100'), $this->exportReviewAttributes($productId)['default']);

        $this->clearChangelog();
        $review->setStatusId(Review::STATUS_NOT_APPROVED)->save();
        $review->aggregate();
        $this->assertContains($productId, $this->getScheduledProductIds());
        $this->assertSame($this->expectedValues('0', '0'), $this->exportReviewAttributes($productId)['default']);

        $review->setStatusId(Review::STATUS_APPROVED)->save();
        $review->aggregate();
        $this->clearChangelog();
        $this->deleteReviews();
        $this->assertContains($productId, $this->getScheduledProductIds());
        $this->assertSame($this->expectedValues('0', '0'), $this->exportReviewAttributes($productId)['default']);
    }

    /**
     * Reviews disabled in a store view are not exported for that store view
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     *
     * @return void
     */
    public function testReviewsDisabledInStoreViewAreNotExported(): void
    {
        $productId = $this->getProductId(self::SKU);
        $secondStoreId = (int)Bootstrap::getObjectManager()->get(StoreManagerInterface::class)
            ->getStore(self::SECOND_STORE)
            ->getId();
        $this->saveReviewActive('0', ScopeInterface::SCOPE_STORES, $secondStoreId);
        $this->createReview($productId, 5);

        $this->assertSame(
            ['default' => $this->expectedValues('1', '100'), self::SECOND_STORE => []],
            $this->exportReviewAttributes($productId)
        );
    }

    /**
     * Changing "Enable Product Reviews" schedules all reviewed products
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     *
     * @return void
     */
    public function testReviewActiveChangeSchedulesReviewedProducts(): void
    {
        $productId = $this->getProductId(self::SKU);
        $this->createReview($productId, 5);
        $this->exportReviewAttributes($productId);

        $this->clearChangelog();
        $this->saveReviewActive('0');
        $this->assertContains($productId, $this->getScheduledProductIds());
        $this->assertSame(
            ['default' => [], self::SECOND_STORE => []],
            $this->exportReviewAttributes($productId)
        );

        $this->clearChangelog();
        $this->saveReviewActive('0');
        $this->assertSame([], $this->getScheduledProductIds(), 'Unchanged value must not schedule products');

        $this->saveReviewActive('1');
        $this->assertContains($productId, $this->getScheduledProductIds());
        $this->assertSame($this->expectedValues('1', '100'), $this->exportReviewAttributes($productId)['default']);
    }

    /**
     * Disabling reviews before any summary was exported does not schedule products
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento_CatalogDataExporter::Test/_files/setup_simple_products.php
     *
     * @return void
     */
    public function testReviewDisableWithoutExportedSummariesIsSkipped(): void
    {
        $this->createReview($this->getProductId(self::SKU), 5);
        // the products feed reindex in setUp() creates the attributes if the database has other reviewed products
        $this->deleteReviewAttributes();
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->removeSharedInstance(ReviewSummaryAttributeRegistry::class);
        $objectManager->removeSharedInstance(ResyncOnReviewActiveChange::class);

        $this->clearChangelog();
        $this->saveReviewActive('0');
        $this->assertSame([], $this->getScheduledProductIds());
    }

    /**
     * Create a review with a rating vote in the default store view and recalculate the summary
     *
     * @param int $productId
     * @param int $stars
     * @param int $status
     * @return Review
     */
    private function createReview(int $productId, int $stars, int $status = Review::STATUS_APPROVED): Review
    {
        $objectManager = Bootstrap::getObjectManager();
        $rating = $this->activateRating();
        $optionId = null;
        $options = $objectManager->create(RatingOptionCollection::class)->addRatingFilter($rating->getId());
        foreach ($options as $option) {
            if ((int)$option->getValue() === $stars) {
                $optionId = (int)$option->getId();
            }
        }

        $storeId = (int)$objectManager->get(StoreManagerInterface::class)->getStore('default')->getId();
        /** @var Review $review */
        $review = $objectManager->create(Review::class);
        $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
            ->setEntityPkValue($productId)
            ->setStatusId($status)
            ->setTitle('Review ' . $stars)
            ->setDetail('Review summary sync test')
            ->setNickname('Tester')
            ->setStoreId($storeId)
            ->setStores([$storeId])
            ->save();
        $objectManager->create(Rating::class)
            ->setRatingId($rating->getId())
            ->setReviewId($review->getId())
            ->addOptionVote($optionId, $productId);
        $review->aggregate();

        return $review;
    }

    /**
     * Activate the test rating in all store views
     *
     * @return Rating
     */
    private function activateRating(): Rating
    {
        $objectManager = Bootstrap::getObjectManager();
        $rating = $objectManager->create(Rating::class)->load(self::RATING_CODE, 'rating_code');
        if ($this->ratingState === null) {
            $this->ratingState = ['stores' => $rating->getStores(), 'is_active' => $rating->getIsActive()];
            $storeIds = array_keys($objectManager->get(StoreManagerInterface::class)->getStores(true));
            $rating->setStores($storeIds)->setIsActive(1)->save();
        }

        return $rating;
    }

    /**
     * Restore the test rating stores and status
     *
     * @return void
     */
    private function restoreRating(): void
    {
        if ($this->ratingState === null) {
            return;
        }
        Bootstrap::getObjectManager()->create(Rating::class)
            ->load(self::RATING_CODE, 'rating_code')
            ->setStores($this->ratingState['stores'] ?: [])
            ->setIsActive($this->ratingState['is_active'])
            ->save();
        $this->ratingState = null;
    }

    /**
     * Delete all reviews of the test product
     *
     * @return void
     */
    private function deleteReviews(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $registry = $objectManager->get(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);
        $productId = $this->getProductId(self::SKU);
        if ($productId) {
            $reviews = $objectManager->create(ReviewCollection::class)
                ->addEntityFilter(Review::ENTITY_PRODUCT_CODE, $productId);
            foreach ($reviews as $review) {
                $review->delete();
            }
        }
        $registry->unregister('isSecureArea');
    }

    /**
     * Delete review summaries of the test product, they are kept by core when reviews are deleted
     *
     * @return void
     */
    private function deleteReviewSummaries(): void
    {
        $productId = $this->getProductId(self::SKU);
        if (!$productId) {
            return;
        }
        $connection = $this->resource->getConnection();
        foreach (['review_entity_summary', 'rating_option_vote_aggregated'] as $table) {
            $connection->delete($this->resource->getTableName($table), ['entity_pk_value = ?' => $productId]);
        }
    }

    /**
     * Save "Enable Product Reviews", as Admin and config:set do
     *
     * Tests save it explicitly because the default differs between installations (enabled in core, disabled in ACCS).
     *
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     * @return void
     */
    private function saveReviewActive(
        string $value,
        string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        int $scopeId = 0
    ): void {
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->create(ValueFactory::class)->create()
            ->setPath(self::REVIEW_ACTIVE)
            ->setScope($scope)
            ->setScopeId($scopeId)
            ->setValue($value)
            ->save();
        $objectManager->get(ReinitableConfigInterface::class)->reinit();
    }

    /**
     * Delete "Enable Product Reviews" saved by the test
     *
     * @return void
     */
    private function deleteReviewActiveConfig(): void
    {
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $resource->getConnection()->delete(
            $resource->getTableName('core_config_data'),
            ['path = ?' => self::REVIEW_ACTIVE]
        );
        Bootstrap::getObjectManager()->get(ReinitableConfigInterface::class)->reinit();
    }

    /**
     * Export the product and get the review summary attributes per store view
     *
     * @param int $productId
     * @return array
     */
    private function exportReviewAttributes(int $productId): array
    {
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->get(EavConfig::class)->clear();
        $metadata = $objectManager->get(self::PRODUCTS_FEED_METADATA);
        $arguments = [['productId' => $productId]];
        $feedItems = [];
        $this->processor->processWithCallback(
            $metadata,
            $arguments,
            function (array $feedItemsRaw) use ($metadata, $arguments, &$feedItems) {
                // process() runs the feed plugins, as the feed indexer does
                $feedItems[] = $this->processor->process($metadata->getFeedName(), $arguments, $feedItemsRaw);
            }
        );

        $exported = [];
        foreach (array_merge([], ...$feedItems) as $product) {
            if (!in_array($product['storeViewCode'], ['default', self::SECOND_STORE], true)) {
                continue;
            }
            $exported[$product['storeViewCode']] = [];
            foreach ($product['attributes'] ?? [] as $attribute) {
                if (in_array($attribute['attributeCode'], ReviewAttributeContract::ATTRIBUTE_CODES, true)) {
                    $exported[$product['storeViewCode']][$attribute['attributeCode']] = $attribute['value'];
                }
            }
        }
        ksort($exported);

        return $exported;
    }

    /**
     * @param string $reviewCount
     * @param string $ratingSummary
     * @return array
     */
    private function expectedValues(string $reviewCount, string $ratingSummary): array
    {
        return [
            ReviewAttributeContract::ATTRIBUTE_REVIEW_COUNT => [$reviewCount],
            ReviewAttributeContract::ATTRIBUTE_RATING_SUMMARY => [$ratingSummary],
        ];
    }

    /**
     * Get product ids in the products feed changelog
     *
     * @return int[]
     */
    private function getScheduledProductIds(): array
    {
        $changelog = $this->productsFeedIndexer->getView()->getChangelog();
        $connection = $this->resource->getConnection();
        $ids = $connection->fetchCol(
            $connection->select()
                ->distinct()
                ->from($this->resource->getTableName($changelog->getName()), [$changelog->getColumnName()])
        );

        return array_map('intval', $ids);
    }

    /**
     * Clear the products feed changelog
     *
     * @return void
     */
    private function clearChangelog(): void
    {
        $changelog = $this->productsFeedIndexer->getView()->getChangelog();
        $this->resource->getConnection()->delete($this->resource->getTableName($changelog->getName()));
    }

    /**
     * Delete review summary attributes created by the test
     *
     * @return void
     */
    private function deleteReviewAttributes(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $eavConfig = $objectManager->get(EavConfig::class);
        $resource = $objectManager->get(ResourceConnection::class);
        $resource->getConnection()->delete(
            $resource->getTableName('eav_attribute'),
            [
                'attribute_code IN (?)' => ReviewAttributeContract::ATTRIBUTE_CODES,
                'entity_type_id = ?' => (int)$eavConfig->getEntityType(Product::ENTITY)->getId(),
            ]
        );
        $eavConfig->clear();
    }
}
