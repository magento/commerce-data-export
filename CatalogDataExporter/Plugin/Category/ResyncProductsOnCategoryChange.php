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
     * @param ScheduleProductUpdateOnCategoryChange $productUpdateSheduler
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        private readonly ScheduleProductUpdateOnCategoryChange $productUpdateSheduler,
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
        $urlKeyChanged = $category->getOrigData('url_key') !== $category->getUrlKey();

        if (!$urlKeyChanged) {
            return $result;
        }
        $this->logger->info(
            sprintf(
                'Category id: "%s" url_key changed from "%s" to "%s". Scheduling product feed update.',
                $category->getId(),
                $category->getOrigData('url_key'),
                $category->getUrlKey(),
            )
        );
        try {
            $this->productUpdateSheduler->execute($category->getPath());
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'ScheduleProductUpdateOnCategoryChange::execute failed: %s',
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
            $this->productUpdateSheduler->execute($newPath);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'ScheduleProductUpdateOnCategoryChange::execute failed: %s',
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
        }

        return $result;
    }
}
