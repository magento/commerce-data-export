<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Plugin\Category;

use Magento\Catalog\Model\ResourceModel\Category;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Plugin to add product ids to the product feed changelog when category url_key or hierarchy (path) changes.
 * If the number of affected products exceeds the threshold, the product feed indexer is invalidated for full reindex.
 */
class ResyncProductsOnCategoryChange
{
    /**
     * @param ScheduleProductUpdateOnCategoryChange $productUpdateScheduler
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        private readonly ScheduleProductUpdateOnCategoryChange $productUpdateScheduler,
        private readonly CommerceDataExportLoggerInterface $logger
    ) {
    }

    /**
     * Run when url_key or path changed: add product ids to changelog or invalidate indexer.
     *
     * If the number of affected products exceeds the threshold, the product feed indexer
     * is invalidated for full reindex; otherwise related product ids are added to the changelog.
     *
     * @param Category $subject
     * @param Category $result
     * @param AbstractModel $category
     * @return Category
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        Category $subject,
        Category $result,
        AbstractModel $category
    ): Category {
        if ($category->isObjectNew()) {
            return $result;
        }
        // on url_key change this and all children categories should be re-synced
        $oldKey = $category->getOrigData('url_key');
        $newKey = $category->getUrlKey();
        $urlKeyChanged = $oldKey !== $newKey;

        // Disabled category should not be returned in Products.categoryData
        $wasActive = (bool)$category->getOrigData('is_active');
        $isActive = (bool)$category->getData('is_active');
        $isActiveChanged = $wasActive !== $isActive;

        if (!$urlKeyChanged && !$isActiveChanged) {
            return $result;
        }

        if ($urlKeyChanged) {
            $this->logger->info(sprintf(
                'Category id: "%s" url_key changed (%s -> %s). Scheduling product feed update.',
                $category->getId(),
                $oldKey,
                $newKey,
            ));
        }
        if ($isActiveChanged) {
            $this->logger->info(sprintf(
                'Category id: "%s" is_active changed (%s -> %s). Scheduling product feed update.',
                $category->getId(),
                (int)$wasActive,
                (int)$isActive,
            ));
        }
        try {
            // include children only when url_key changes - is_active affects the category itself only
            $this->productUpdateScheduler->execute($category->getPath(), $urlKeyChanged);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'CDE03-04 Product sync scheduling error on url key change (%s -> %s). Run resync. Error: %s',
                    $oldKey,
                    $newKey,
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
        }

        return $result;
    }

    /**
     * Schedule update on category move.
     *
     * @param Category $subject
     * @param Category $result
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\Category $newParent
     * @param int|null $afterCategoryId
     * @return Category
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterChangeParent(
        Category $subject,
        Category $result,
        \Magento\Catalog\Model\Category $category,
        \Magento\Catalog\Model\Category $newParent,
        $afterCategoryId
    ) {
        $oldPath = $category->getOrigData('path');
        $newPath = $newParent->getPath() . '/' . $category->getId();
        $this->logger->info(
            sprintf(
                'Category path changed from "%s" to "%s". Scheduling product feed update.',
                $oldPath,
                $newPath
            )
        );
        try {
            $this->productUpdateScheduler->execute($newPath);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'CDE03-05 Product sync scheduling error on category path change (%s -> %s). Run resync. Error: %s',
                    $oldPath,
                    $newPath,
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
        }

        return $result;
    }
}
