<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\Processor;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Abstract Class AbstractProductPricesTestHelper
 */
abstract class AbstractProductPriceTestHelper extends TestCase
{
    /**
     * Test Constants
     */
    private const PRODUCT_PRICE_FEED_INDEXER = 'catalog_data_exporter_product_prices';

    /**
     * Data Exporter Price Indexer table
     */
    private const PRODUCT_PRICE_FEED_TABLE = 'cde_product_prices_feed';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var WebsiteRepositoryInterface
     */
    protected $websiteRepository;

    private static ?string $version;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $this->indexer = $this->objectManager->create(Indexer::class);
        $this->resourceConnection = $this->objectManager->create(ResourceConnection::class);
        $this->websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);

        $this->objectManager->configure([
            'Magento\ProductPriceDataExporter\Model\Indexer\ProductPriceFeedIndexMetadata' => [
                'arguments' => [
                    'persistExportedFeed' => true
                ]
            ]
        ]);

        $this->indexer->load(self::PRODUCT_PRICE_FEED_INDEXER);
        $this->reindexProductPriceDataExporter();
    }

    /**
     * Wait one second before test execution after fixtures created.
     *
     * @return void
     */
    protected function emulateCustomersBehaviorAfterDeleteAction(): void
    {
        // Avoid getFeed right after product was created.
        // We have to emulate real customers behavior
        // as it's possible that feed in test will be retrieved in same time as product created:
        // \Magento\DataExporter\Model\Query\RemovedEntitiesByModifiedAtQuery::getQuery
        sleep(1);
    }

    /**
     * Verify that exported product prices feed contains deleted items
     *
     * @param array $expectedIds
     * @return void
     */
    protected function checkExportedDeletedItems(array $expectedIds): void
    {
        $this->emulateCustomersBehaviorAfterDeleteAction();
        $processor = $this->objectManager->create(Processor::class);
        $processor->updateMview();
        $processor->reindexAllInvalid();

        $actualProductPricesFeed = $this->getExtractedProductPrices($expectedIds);
        self::assertNotEmpty($actualProductPricesFeed, 'Product Price Feed should not be empty');
        foreach ($actualProductPricesFeed as $feedItems) {
            if (array_contains($expectedIds, (string)$feedItems['feed']['productId'])) {
                self::assertTrue(
                    (bool)$feedItems['is_deleted'],
                    "Product Price Feed with key: " . $feedItems['feed']['sku'] . '_'
                    . $feedItems['feed']['websiteCode'] . '_'
                    . $feedItems['feed']['customerGroupCode']
                    . " should be deleted"
                );
            }
        }
    }
    /**
     * @param array $expectedItems
     * @return void
     * @throws NoSuchEntityException
     */
    protected function checkExpectedItemsAreExportedInFeed(array $expectedItems): void
    {
        $this->emulateCustomersBehaviorAfterDeleteAction();
        $processor = $this->objectManager->create(Processor::class);
        $processor->updateMview();
        $processor->reindexAllInvalid();
        $expectedIds = [];
        foreach ($expectedItems as $expectedItem) {
            $expectedIds[] = $this->productRepository->get($expectedItem['sku'])->getId();
        }
        $actualProductPricesFeed = $this->getExtractedProductPrices($expectedIds);
        self::assertNotEmpty($actualProductPricesFeed, 'Product Price Feed should not be empty');
        $feedsToCheck = [];
        foreach ($actualProductPricesFeed as $feedItems) {
            $productPrice = $feedItems['feed'];
            if (array_contains($expectedIds, (string)$productPrice['productId'])) {
                $itemKey = $this->buildPriceKey($productPrice);
                $feedsToCheck[$itemKey] = $this->unsetNotImportantField($productPrice);
            }
        }
        if (!empty($feedsToCheck)) {
            self::assertCount(
                count($expectedIds),
                $feedsToCheck,
                'Product Price Feeds does not contain all expected items'
            );
        } else {
            self::fail("There are no expected products in the feed");
        }

        foreach ($expectedItems as $expectedKey => $expectedItem) {
            if (!isset($feedsToCheck[$expectedKey])) {
                self::fail("Cannot find product price feed with key: " . $expectedKey);
            }
            $actualFeed = $this->arrangeExpectedItems($expectedItem, $feedsToCheck[$expectedKey]);
            self::assertEqualsCanonicalizing(
                $expectedItem,
                $actualFeed,
                "Some items are missing in product price feed "
                . $expectedItem['sku'] . '_'
                . $expectedItem['websiteCode'] . '_'
                . $expectedItem['customerGroupCode'] . ' feed'
            );
        }
    }

    private function arrangeExpectedItems(array $expectedData, $actualData): array
    {
        $normalizedItems = [];
        foreach (array_keys($expectedData) as $key) {
            if (!\array_key_exists($key, $actualData)) {
                self::fail("Cannot find data with key: " . $key);
            }
            $normalizedItems[$key] = $actualData[$key];
        }
        return array_replace($normalizedItems, $actualData);
    }

    /**
     * Reindex all the product price data exporter table for existing products
     *
     * @return void
     */
    private function reindexProductPriceDataExporter() : void
    {
        $searchCriteria = Bootstrap::getObjectManager()->create(SearchCriteriaInterface::class);

        $productIds = array_map(
            fn($product) => $product->getId(),
            $this->productRepository->getList($searchCriteria)->getItems()
        );

        if (!empty($productIds)) {
            $this->indexer->reindexList($productIds);
        }
    }

    /**
     * @param array $actualProductPricesFeed
     * @return array
     */
    private function unsetNotImportantField(array $actualProductPricesFeed): array
    {
        $actualFeed = $actualProductPricesFeed;

        unset(
            $actualFeed['modifiedAt'],
            $actualFeed['updatedAt'],
            $actualFeed['productId'],
            $actualFeed['websiteId']
        );

        return $actualFeed;
    }

    /**
     * Truncates index table
     */
    private function truncateIndexTable(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $feedTable = $this->resourceConnection->getTableName(self::PRODUCT_PRICE_FEED_TABLE);
        $connection->truncateTable($feedTable);
    }

    private function getExtractedProductPrices(array $productIds) : array
    {
        $connection = $this->resourceConnection->getConnection();
        $query = $connection->select()
            ->from(['ex' => $this->resourceConnection->getTableName(self::PRODUCT_PRICE_FEED_TABLE)])
            ->where('ex.source_entity_id IN (?)', $productIds);
        $cursor = $connection->query($query);
        $data = [];
        while ($row = $cursor->fetch()) {
            $feed = \json_decode((string) $row['feed_data'], true);
            $key = $this->buildPriceKey($feed);
            $data[$key]['modified_at'] = $row['modified_at'];
            $data[$key]['is_deleted'] = $row['is_deleted'];
            $data[$key]['feed'] = $feed;
        }
        return $data;
    }

    private function buildPriceKey(array $row): string
    {
        return $row['sku'] . '_' . $row['websiteCode'] . '_' . $row['customerGroupCode'];
    }

    /**
     * @param string $sku
     * @return void
     * @throws NoSuchEntityException
     */
    protected function deleteProduct(string $sku): void
    {
        $productToDelete = $this->productRepository->get($sku);
        /** @var \Magento\Framework\Registry $registry */
        $registry = Bootstrap::getObjectManager()->get(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);
        try {
            $this->productRepository->delete($productToDelete);
        } catch (\Exception $e) {
            self::fail(
                "Failed to delete product with SKU: $sku. Error: " . $e->getMessage()
            );
        }

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->truncateIndexTable();
    }

    protected static function getPriceForVersion(float $price): float
    {
        if (empty(self::$version)) {
            $rawVersion = Bootstrap::getObjectManager()->get(ProductMetadataInterface::class)->getVersion();
            self::$version = preg_replace('/.*?(\d\.\d\.\d(?:-\w+)?).*/', '$1', (string) $rawVersion);
        }
        return version_compare(self::$version, '2.4.9-dev', '>=') ? $price : round($price, 2);
    }
}
