<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Category;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;

/**
 * Schedule product update when category url_key or hierarchy (path) changes.
 * If the number of affected products exceeds the threshold, the product feed indexer is invalidated for full reindex.
 * TODO: move product threshold to env config to be able configure for edge case
 */
class ScheduleProductUpdateOnCategoryChange
{
    private const PRODUCTS_FEED_INDEXER = 'catalog_data_exporter_products';
    private const CATALOG_CATEGORY_PRODUCT_TABLE = 'catalog_category_product';
    private const CATALOG_CATEGORY_ENTITY_TABLE = 'catalog_category_entity';
    private const AFFECTED_PRODUCTS_THRESHOLD = 1000;

    /**
     * @param ResourceConnection $resourceConnection
     * @param IndexerRegistry $indexerRegistry
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly IndexerRegistry $indexerRegistry,
        private readonly CommerceDataExportLoggerInterface $logger
    ) {
    }

    /**
     * For given category path
     * - add related product ids to the changelog
     * - or invalidate the product feed indexer when too many products are affected.
     *
     * @param string $categoryPath
     * @return void
     */
    public function execute(
        string $categoryPath
    ): void {
        if ($categoryPath === '' || !str_contains($categoryPath, '/')) {
            $this->logger->warning(
                sprintf(
                    'Skipping product feed update scheduling. Category path "%s" is wrongly formatted',
                    $categoryPath
                )
            );
            return ;
        }
        $affectedCategoryIds = $this->getAffectedCategoryIds($categoryPath);
        if (empty($affectedCategoryIds)) {
            return ;
        }

        $updateOnScheduleMode = $this->indexerRegistry->get(self::PRODUCTS_FEED_INDEXER)->getView()->isEnabled();
        if (!$updateOnScheduleMode) {
            $this->indexerRegistry->get(self::PRODUCTS_FEED_INDEXER)->invalidate();
            $this->logger->info(
                'Category change detected. Full resync scheduled due to product feed is not in schedule update mode'
            );
            return ;
        }

        $affectedProductCount = $this->getAffectedProductCount($affectedCategoryIds);
        if ($affectedProductCount <= 0) {
            return ;
        }
        if ($affectedProductCount > self::AFFECTED_PRODUCTS_THRESHOLD) {
            $this->indexerRegistry->get(self::PRODUCTS_FEED_INDEXER)->invalidate();
        } else {
            $this->addProductIdsToChangelog($affectedCategoryIds);
        }

        $this->logger->info(sprintf(
            'Category change detected. Affected # of categories: %d, products: %d. %s.',
            count($affectedCategoryIds),
            $affectedProductCount,
            $affectedProductCount > self::AFFECTED_PRODUCTS_THRESHOLD
                ? 'Full resync scheduled'
                : 'Partial resync scheduled'
        ));
    }

    /**
     * Get current category id and all descendant category ids (path pattern "1/2/5/6" for descendants).
     *
     * @param string $categoryPath
     * @return int[]
     */
    private function getAffectedCategoryIds(string $categoryPath): array
    {
        $currentId = explode('/', $categoryPath);
        $currentId = (int) end($currentId);

        $connection = $this->resourceConnection->getConnection();
        $categoryEntityTable = $this->resourceConnection->getTableName(self::CATALOG_CATEGORY_ENTITY_TABLE);

        $descendantIds = $connection->fetchCol(
            $connection->select()
                ->from(
                    ['main_table' => $categoryEntityTable],
                    ['entity_id']
                )
                ->where('main_table.path LIKE ?', $categoryPath . '/%')
        );

        $descendantIds = array_map('intval', $descendantIds);

        return array_merge([$currentId], $descendantIds);
    }

    /**
     * Count category-product rows for the given categories, capped by threshold + 1.
     *
     * Fast: SELECT COUNT(1) FROM (SELECT 1 FROM catalog_category_product WHERE category_id IN (?) LIMIT threshold+1) t
     *
     * @param int[] $categoryIds
     * @return int
     */
    private function getAffectedProductCount(array $categoryIds): int
    {
        if (empty($categoryIds)) {
            return 0;
        }

        $connection = $this->resourceConnection->getConnection();
        $categoryProductTable = $this->resourceConnection->getTableName(self::CATALOG_CATEGORY_PRODUCT_TABLE);

        $subSelect = $connection->select()
            ->from(
                ['t' => $categoryProductTable],
                [new Expression('1')]
            )
            ->where('t.category_id IN (?)', $categoryIds)
            ->limit(self::AFFECTED_PRODUCTS_THRESHOLD + 1);

        $select = $connection->select()
            ->from(
                ['t' => $subSelect],
                [new Expression('COUNT(1)')]
            );

        return (int) $connection->fetchOne($select);
    }

    /**
     * Insert distinct product ids from affected categories into the product feed changelog (single query).
     *
     * @param int[] $affectedCategoryIds
     * @return void
     */
    private function addProductIdsToChangelog(array $affectedCategoryIds): void
    {
        $connection = $this->resourceConnection->getConnection();
        $categoryProductTable = $this->resourceConnection->getTableName(self::CATALOG_CATEGORY_PRODUCT_TABLE);
        $changelogTableName = $this->indexerRegistry->get(self::PRODUCTS_FEED_INDEXER)
            ->getView()
            ->getChangelog()
            ->getName();
        $changelogTable = $this->resourceConnection->getTableName($changelogTableName);
        $changelogColumnName = 'entity_id';

        $select = $connection->select()
            ->distinct()
            ->from(
                ['main_table' => $categoryProductTable],
                [$changelogColumnName => 'main_table.product_id']
            )
            ->where('main_table.category_id IN (?)', $affectedCategoryIds);

        $connection->query(
            $connection->insertFromSelect($select, $changelogTable, [$changelogColumnName])
        );
    }
}
