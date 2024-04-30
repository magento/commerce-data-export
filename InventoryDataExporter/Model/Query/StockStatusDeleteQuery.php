<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Query;

use Magento\DataExporter\Model\FeedHashBuilder;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime;

/**
 * Stock Status mark as deleted query builder
 */
class StockStatusDeleteQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var FeedIndexMetadata
     */
    private $metadata;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DateTime
     */
    private $dateTime;

    private FeedHashBuilder $hashBuilder;

    /**
     * @param ResourceConnection $resourceConnection
     * @param FeedIndexMetadata $metadata
     * @param SerializerInterface $serializer
     * @param DateTime $dateTime
     * @param ?FeedHashBuilder $hashBuilder
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        FeedIndexMetadata $metadata,
        SerializerInterface $serializer,
        DateTime $dateTime,
        ?FeedHashBuilder $hashBuilder
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadata = $metadata;
        $this->serializer = $serializer;
        $this->dateTime = $dateTime;
        $this->hashBuilder = $hashBuilder ?? ObjectManager::getInstance()->get(FeedHashBuilder::class);
    }

    /**
     * Get stocks which are assigned to the list of provided SKUs
     *
     * @param array $skus
     * @return array
     */
    public function getStocksAssignedToSkus(array $skus): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['source_item' => $this->resourceConnection->getTableName('inventory_source_item')],
                ['source_item.sku', 'source_stock_link.stock_id', 'source_stock_link.source_code']
            )->joinLeft(
                ['source_stock_link' => $this->resourceConnection->getTableName('inventory_source_stock_link')],
                'source_item.source_code = source_stock_link.source_code',
                []
            )->joinLeft(
                ['products' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'source_item.sku = products.sku',
                ['products.entity_id AS product_id']
            )->where('source_item.sku IN (?)', $skus);

        $fetchedSourceItems = [];
        foreach ($connection->fetchAll($select) as $sourceItem) {
            $fetchedSourceItems[$sourceItem['sku']]['product_id'] = $sourceItem['product_id'];
            $fetchedSourceItems[$sourceItem['sku']]['stock'][$sourceItem['stock_id']][] = $sourceItem['source_code'];
        }

        return $fetchedSourceItems;
    }

    /**
     * Get product ids for provided SKUs
     *
     * @param array $skus
     * @return array
     */
    public function getProductIdsForSkus(array $skus): array
    {
        $output = [];

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['catalog_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                ['catalog_product_entity.sku', 'catalog_product_entity.entity_id AS product_id']
            )->where('catalog_product_entity.sku IN (?)', $skus);

        foreach ($connection->fetchAll($select) as $productData) {
            $output[$productData['sku']] = $productData['product_id'];
        }
        return $output;
    }

    /**
     * Get stocks which are assigned to the list of provided SKUs
     *
     * @param array $sourceCodes
     * @return array
     */
    public function getStocksWithSources(array $sourceCodes): array
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceLinkTableName = $this->resourceConnection->getTableName('inventory_source_stock_link');
        $select = $connection->select()
            ->from(
                ['source_stock_link' => $sourceLinkTableName],
                ['source_stock_link.stock_id', 'source_stock_link_all_sources.source_code']
            )->joinInner(
                ['source_stock_link_all_sources' => $sourceLinkTableName],
                'source_stock_link_all_sources.stock_id = source_stock_link.stock_id',
                []
            )->where(
                'source_stock_link.source_code IN (?)',
                $sourceCodes
            )->group(
                ['source_stock_link.stock_id',
                    'source_stock_link_all_sources.source_code'
                ]
            );
        $stocks = [];
        foreach ($connection->fetchAll($select) as $stockData) {
            $stocks[$stockData['stock_id']][] = $stockData['source_code'];
        }
        return $stocks;
    }

    /**
     * Mark stock statuses as deleted
     *
     * @param array $idsToDelete
     */
    public function markStockStatusesAsDeleted(array $idsToDelete): void
    {
        $records = [];
        foreach ($idsToDelete as $stockStatusData) {
            $records[] = $this->buildFeedData($stockStatusData);
        }
        $connection = $this->resourceConnection->getConnection();
        $feedTableName = $this->resourceConnection->getTableName($this->metadata->getFeedTableName());
        $chunks = array_chunk($records, $this->metadata->getBatchSize());
        foreach ($chunks as $chunk) {
            $connection->insertOnDuplicate(
                $feedTableName,
                $chunk
            );
        }
    }

    /**
     * @param array $stockData
     * @return array
     */
    private function buildFeedData( array $stockData): array
    {
        if (!isset($stockData['stockId'], $stockData['sku'], $stockData['productId'])) {
            throw new \RuntimeException(
                sprintf(
                    "inventory_data_exporter_stock_status indexer error: cannot build feed data from %s",
                    \var_export($stockData, true)
                )
            );
        }
        $feedData = [
            'stockId' => $stockData['stockId'],
            'sku' => $stockData['sku'],
            'productId' => $stockData['productId'],
            'qty' => 0,
            'qtyForSale' => 0,
            'infiniteStock' => false,
            'isSalable' => false,
            'updatedAt' => $this->dateTime->formatDate(time())

        ];

        return [
            FeedIndexMetadata::FEED_TABLE_FIELD_FEED_ID => $this->hashBuilder->buildIdentifierFromFeedItem(
                $stockData,
                $this->metadata
            ),
            'feed_data' => $this->serializer->serialize($feedData),
            'is_deleted' => 1,
            'modified_at' => $this->dateTime->formatDate(time())
        ];
    }
}
