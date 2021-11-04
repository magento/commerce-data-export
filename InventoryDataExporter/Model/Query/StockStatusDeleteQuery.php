<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
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

    /**
     * @param ResourceConnection $resourceConnection
     * @param FeedIndexMetadata $metadata
     * @param SerializerInterface $serializer
     * @param DateTime $dateTime
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        FeedIndexMetadata $metadata,
        SerializerInterface $serializer,
        DateTime $dateTime
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadata = $metadata;
        $this->serializer = $serializer;
        $this->dateTime = $dateTime;
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
            )->where('source_item.sku IN (?)', $skus);

        $fetchedSourceItems = [];
        foreach ($connection->fetchAll($select) as $sourceItem) {
            $fetchedSourceItems[$sourceItem['sku']][$sourceItem['stock_id']][] = $sourceItem['source_code'];
        }

        return $fetchedSourceItems;
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
        foreach ($idsToDelete as $deletedItemId => $stockStatusData) {
            $records[] = $this->buildFeedData($deletedItemId, $stockStatusData);
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
     * @param string $stockStatusId
     * @param array $stockIdAndSku
     * @return array
     */
    private function buildFeedData(string $stockStatusId, array $stockIdAndSku): array
    {
        if (!isset($stockIdAndSku['stock_id'], $stockIdAndSku['sku'])) {
            throw new \RuntimeException(
                sprintf(
                    "inventory_data_exporter_stock_status indexer error: cannot build unique id from %s",
                    \var_export($stockIdAndSku, true)
                )
            );
        }
        $feedData = [
            'stockId' => $stockIdAndSku['stock_id'],
            'sku' => $stockIdAndSku['sku'],
            'qty' => 0,
            'qtyForSale' => 0,
            'infiniteStock' => false,
            'isSalable' => false,
            'updatedAt' => $this->dateTime->formatDate(time())

        ];

        return [
            'stock_id' => $stockIdAndSku['stock_id'],
            'sku' => $stockIdAndSku['sku'],
            'feed_data' => $this->serializer->serialize($feedData),
            'is_deleted' => 1,
            'modified_at' => $this->dateTime->formatDate(time())
        ];
    }
}
