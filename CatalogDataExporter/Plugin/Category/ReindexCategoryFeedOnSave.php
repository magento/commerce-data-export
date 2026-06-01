<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\CatalogDataExporter\Model\Query\Category\ChildCategoriesQuery;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Mview\ViewFactory;

/**
 * Schedule sync for children categories when top-level category isActive/IncludeInMenu changed
 */
class ReindexCategoryFeedOnSave
{
    /**
     * Mview view id for the category feed
     */
    private const CATEGORY_FEED_VIEW_ID = 'cde_categories_feed';

    /** @var ChildCategoriesQuery */
    private ChildCategoriesQuery $childCategoriesQuery;

    /** @var CommerceDataExportLoggerInterface */
    private CommerceDataExportLoggerInterface $logger;

    /** @var ResourceConnection */
    private ResourceConnection $resourceConnection;

    /** @var ViewFactory */
    private ViewFactory $viewFactory;

    /**
     * @param ChildCategoriesQuery $childCategoriesQuery
     * @param CommerceDataExportLoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ViewFactory $viewFactory
     */
    public function __construct(
        ChildCategoriesQuery $childCategoriesQuery,
        CommerceDataExportLoggerInterface $logger,
        ResourceConnection $resourceConnection,
        ViewFactory $viewFactory
    ) {
        $this->childCategoriesQuery = $childCategoriesQuery;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->viewFactory = $viewFactory;
    }

    /**
     * Reindex category feed on save
     *
     * @param CategoryResource $subject
     * @param \Closure $proceed
     * @param Category $category
     * @return CategoryResource
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(CategoryResource $subject, \Closure $proceed, AbstractModel $category)
    {
        // Capture before-save values; getOrigData() is reset to current data after save() completes
        $wasActive = (bool)$category->getOrigData('is_active');
        $wasInMenu = (bool)$category->getOrigData('include_in_menu');
        $result = $proceed($category);
        try {
            if ($this->isTopLevelCategory($category)
                && $this->isStatusChanged($category, $wasActive, $wasInMenu)
            ) {
                $this->scheduleChildrenReindex($category);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'CDE03-03 Categories sync error on category "%s" save. Run resync. Error: %s',
                    $category->getUrlKey(),
                    $e->getMessage(),
                ),
                ['exception' => $e]
            );
        }
        return $result;
    }

    /**
     * Returns true when the category is a top-level storefront category (path has exactly 3 segments, e.g. "1/2/100").
     *
     * Only top-level categories act as ancestor propagation roots for their descendants.
     *
     * @param AbstractModel $subject
     * @return bool
     */
    private function isTopLevelCategory(AbstractModel $subject): bool
    {
        return count(explode('/', (string)$subject->getPath())) === 3;
    }

    /**
     * Returns true when is_active or include_in_menu changed in either direction.
     *
     * Any change in these fields propagates to descendants via AncestorStatusProvider, so they need reindexing.
     *
     * @param AbstractModel $subject
     * @param bool $wasActive
     * @param bool $wasInMenu
     * @return bool
     */
    private function isStatusChanged(AbstractModel $subject, bool $wasActive, bool $wasInMenu): bool
    {
        return $wasActive !== (bool)$subject->getData('is_active')
            || $wasInMenu !== (bool)$subject->getData('include_in_menu');
    }

    /**
     * Inserts descendant category IDs into the changelog
     *
     * @param AbstractModel $subject
     * @return void
     */
    private function scheduleChildrenReindex(AbstractModel $subject): void
    {
        $children = $this->childCategoriesQuery->getAllChildrenIds($subject);
        if (!$children) {
            return;
        }
        $view = $this->viewFactory->create()->load(self::CATEGORY_FEED_VIEW_ID);
        $changelog = $view->getChangelog();
        $changelogTable = $this->resourceConnection->getTableName($changelog->getName());
        $connection = $this->resourceConnection->getConnection();
        $connection->insertArray($changelogTable, [$changelog->getColumnName()], $children);
    }
}
