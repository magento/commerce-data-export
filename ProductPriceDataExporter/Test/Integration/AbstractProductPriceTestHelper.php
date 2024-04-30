<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
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
    protected WebsiteRepositoryInterface $websiteRepository;

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

        $this->indexer->load(self::PRODUCT_PRICE_FEED_INDEXER);
        $this->reindexProductPriceDataExporter();
    }

    /**
     * @param array $expectedItems
     * @return void
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    protected function checkExpectedItemsAreExportedInFeed(array $expectedItems): void
    {
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
            self::assertEquals(
                $expectedItem,
                $feedsToCheck[$expectedKey],
                "Some items are missing in product price feed " . $expectedItem['sku']
            );
        }
    }

    /**
     * Reindex all the product price data exporter table for existing products
     *
     * @return void
     */
    private function reindexProductPriceDataExporter() : void
    {
        $searchCriteria = Bootstrap::getObjectManager()->create(SearchCriteriaInterface::class);

        $productIds = array_map(function ($product) {
            return $product->getId();
        }, $this->productRepository->getList($searchCriteria)->getItems());

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
            $feed = \json_decode($row['feed_data'], true);
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
     * @return void
     */
    protected function tearDown(): void
    {
        $this->truncateIndexTable();
    }
}
