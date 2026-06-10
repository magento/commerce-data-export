<?php
/**
 * Copyright 2024 Adobe
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
 * Without this patch, deploying the urlPath+storeViewCode identity change causes a delete storm:
 * the reindex emits new-identity upserts AND marks old-identity rows is_deleted=1 for the same
 * categories, which wipes all categories in ACO/SaaS. Clearing the stale rows and forcing a full
 * reindex eliminates the old-identity rows entirely so no spurious deletes are produced.
 *
 * After the DELETE, any active rows still in the table are orphans — categories removed from catalog
 * before deploy but not yet materialized as is_deleted=1. They are converted to tombstones here so
 * SaaS receives the delete signal on the next export instead of keeping phantom categories.
 */
class MigrateCategoryFeedIdentity implements DataPatchInterface
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

        // Remaining active rows belong to categories deleted before deploy but never synced.
        // Mark them as tombstones with retryable status so the next export submits the deletes.
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
