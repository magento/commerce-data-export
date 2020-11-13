<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Plugin;

use Magento\Catalog\Model\ResourceModel\Product as ResourceProduct;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;
use Magento\ProductVariantDataExporter\Model\Indexer\ProductVariantFeedIndexer;
use Magento\ProductVariantDataExporter\Model\Indexer\UpdateChangeLog;
use Magento\ProductVariantDataExporter\Model\Query\ProductRelationsQuery;

/**
 * Plugin to trigger reindex on product variants upon product deletion
 */
class ReindexVariantsOnDelete
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var UpdateChangeLog
     */
    private $updateChangeLog;

    /**
     * @var ProductRelationsQuery
     */
    private $productRelationsQuery;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param UpdateChangeLog $updateChangeLog
     * @param ProductRelationsQuery $productRelationsQuery
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        UpdateChangeLog $updateChangeLog,
        ProductRelationsQuery $productRelationsQuery
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->updateChangeLog = $updateChangeLog;
        $this->productRelationsQuery = $productRelationsQuery;
    }

    /**
     * Reindex product variants on product deletion
     *
     * @param ResourceProduct $subject
     * @param \Closure $proceed
     * @param AbstractModel $product
     * @return ResourceProduct
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDelete(
        ResourceProduct $subject,
        \Closure $proceed,
        AbstractModel $product
    ): ResourceProduct {
        $ids = $this->productRelationsQuery->getRelationsParentIds([$product->getId()]);
        $result = $proceed($product);
        if (!empty($ids)) {
            $this->reindexVariants($ids);
        }
        return $result;
    }

    /**
     * Reindex product variants
     *
     * @param int[] $ids
     * @return void
     */
    private function reindexVariants(array $ids): void
    {
        $indexer = $this->indexerRegistry->get(ProductVariantFeedIndexer::INDEXER_ID);

        if ($indexer->isScheduled()) {
            $this->updateChangeLog->execute($indexer->getViewId(), $ids);
        } else {
            $indexer->reindexList($ids);
        }
    }
}
