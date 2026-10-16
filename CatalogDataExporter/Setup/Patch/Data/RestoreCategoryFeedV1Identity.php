<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Setup\Patch\Data;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Removes cde_categories_feed rows for all existing categories and invalidates the feed indexer.
 *
 * Restores category feed identity to categoryId and storeViewCode for V1 Catalog Service.
 * Removing stale path-identity rows and forcing a full reindex ensures feed items are rebuilt
 * under the canonical categoryId identity without producing previous-path deletion tombstones.
 */
class RestoreCategoryFeedV1Identity implements DataPatchInterface
{
    private const CATEGORY_FEED_INDEXER = 'catalog_data_exporter_categories';

    /**
     * @param ResourceConnection $resourceConnection DB connection
     * @param FeedIndexMetadata $categoryFeedIndexMetadata Category feed metadata
     * @param IndexerRegistry $indexerRegistry Indexer registry
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly FeedIndexMetadata $categoryFeedIndexMetadata,
        private readonly IndexerRegistry $indexerRegistry
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $connection = $this->resourceConnection->getConnection();
        $feedTable = $this->resourceConnection->getTableName(
            $this->categoryFeedIndexMetadata->getFeedTableName()
        );
        $categoryTable = $this->resourceConnection->getTableName('catalog_category_entity');

        $connection->delete(
            $feedTable,
            new \Zend_Db_Expr(
                'source_entity_id IN (SELECT entity_id FROM ' . $categoryTable . ')'
            )
        );

        $connection->update(
            $feedTable,
            [
                FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED => 1,
                FeedIndexMetadata::FEED_TABLE_FIELD_STATUS     => ExportStatusCodeProvider::RETRYABLE,
            ],
            [FeedIndexMetadata::FEED_TABLE_FIELD_IS_DELETED . ' = ?' => 0]
        );

        $this->indexerRegistry->get(self::CATEGORY_FEED_INDEXER)->invalidate();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
