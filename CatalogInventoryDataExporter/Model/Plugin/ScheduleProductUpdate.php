<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogInventoryDataExporter\Model\Plugin;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Schedule reindex for "product feed indexer" if Stock Status updated.
 * Out of the box we can't use standard mview.xml configuration to listen to changes on stock table
 * `inventory_stock_<stock_id>` since mview doesn't support subscribing on dynamic tables
 */
class ScheduleProductUpdate
{
    private const FEED_INDEXER = 'catalog_data_exporter_products';

    private IndexerRegistry $indexerRegistry;
    private IndexerFactory $indexerFactory;
    private ResourceConnection $resourceConnection;
    private LoggerInterface $logger;

    /**
     * @param IndexerFactory $indexerFactory
     * @param IndexerRegistry $indexerRegistry
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        IndexerFactory $indexerFactory,
        IndexerRegistry $indexerRegistry,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->indexerFactory = $indexerFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Add product ids to changelog
     *
     * @param array $productSkus
     * @return void
     */
    public function execute(array $productSkus): void
    {
        try {
            $productIndexer = $this->indexerRegistry->get(self::FEED_INDEXER);
            if (!empty($productSkus) && $productIndexer->isScheduled()) {
                $productIds = $this->getProductIdsFromSkus($productSkus);
                if (!$productIds) {
                    $this->logger->warning("Cannot get product ids from SKUs: " . var_export($productSkus, true));
                    return ;
                }
                $this->updateChangelog($productIds);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Cannot update indexer during inventory source item save: ' . $e->getMessage());
        }
    }

    /**
     * Update change log
     *
     * @param array $productIds
     * @return void
     */
    private function updateChangelog(array $productIds): void
    {
        $connection = $this->resourceConnection->getConnection();
        $view = $this->indexerFactory->create()->load(self::FEED_INDEXER)->getView();
        $tableName = $view->getChangelog()->getName();
        $realTableName = $this->resourceConnection->getTableName($tableName);
        $connection->insertArray($realTableName, ['entity_id'], $productIds);
    }

    /**
     * Get product ids from skus
     *
     * @param array $productSkus
     * @return array
     */
    private function getProductIdsFromSkus(array $productSkus): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['e' => $this->resourceConnection->getTableName('catalog_product_entity')],
                ['entity_id']
            )->where('sku IN (?)', $productSkus);

        return $connection->fetchCol($select);
    }
}
