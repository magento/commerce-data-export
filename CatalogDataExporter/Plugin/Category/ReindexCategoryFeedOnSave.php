<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Category;

use Magento\Catalog\Model\Category;
use Magento\CatalogDataExporter\Model\Query\Category\ChildCategoriesQuery;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Plugin for running category feed indexation during saving / deleting process
 */
class ReindexCategoryFeedOnSave
{
    /**
     * Category feed indexer id
     */
    private const CATEGORY_FEED_INDEXER = 'catalog_data_exporter_categories';

    private ChildCategoriesQuery $childCategoriesQuery;
    private IndexerRegistry $indexerRegistry;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param ChildCategoriesQuery $childCategoriesQuery
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        ChildCategoriesQuery $childCategoriesQuery,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->childCategoriesQuery = $childCategoriesQuery;
        $this->logger = $logger;
    }

    /**
     * Reindex category feed on save
     *
     * @param Category $subject
     *
     * @return void
     */
    public function afterReindex(Category $subject) : void
    {
        try {
            $categoryFeedIndexer = $this->indexerRegistry->get(self::CATEGORY_FEED_INDEXER);
            if (!$categoryFeedIndexer->isScheduled()) {
                $children = $this->childCategoriesQuery->getAllChildrenIds($subject);
                $idsList = array_unique(array_merge([$subject->getId()], $children, $subject->getParentIds()));
                $categoryFeedIndexer->reindexList($idsList);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
