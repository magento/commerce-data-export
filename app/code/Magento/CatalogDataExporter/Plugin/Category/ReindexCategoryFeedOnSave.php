<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Category;

use Magento\Catalog\Model\Category;
use Magento\CatalogDataExporter\Model\Query\Category\ChildCategoriesQuery;
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

    /**
     * @var ChildCategoriesQuery
     */
    private $childCategoriesQuery;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param ChildCategoriesQuery $childCategoriesQuery
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        ChildCategoriesQuery $childCategoriesQuery
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->childCategoriesQuery = $childCategoriesQuery;
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
        $categoryFeedIndexer = $this->indexerRegistry->get(self::CATEGORY_FEED_INDEXER);
        if (!$categoryFeedIndexer->isScheduled()) {
            $children = $this->childCategoriesQuery->getAllChildrenIds($subject);
            $idsList = array_unique(array_merge([$subject->getId()], $children, $subject->getParentIds()));
            $categoryFeedIndexer->reindexList($idsList);
        }
    }
}
