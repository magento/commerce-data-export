<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Relation;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\ProductVariantDataExporter\Model\Indexer\ProductVariantFeedIndexer;

/**
 * Plugin to trigger reindex on variants when relations are changed
 */
class ReindexVariantsOnRelationsChange
{

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Reindex variants after additon of new relations
     *
     * @param Relation $subject
     * @param Relation $result
     * @param int $parentId
     * @param int[] $childIds
     * @return Relation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddRelations(
        Relation $subject,
        Relation $result,
        int $parentId,
        array $childIds
    ): Relation {
        if (!empty($childIds)) {
            $this->reindexVariants($parentId);
        }
        return $result;
    }

    /**
     * Reindex variants after removal of relations
     *
     * @param Relation $subject
     * @param Relation $result
     * @param int $parentId
     * @param int[] $childIds
     * @return Relation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRemoveRelations(
        Relation $subject,
        Relation $result,
        int $parentId,
        array $childIds
    ): Relation {
        if (!empty($childIds)) {
            $this->reindexVariants($parentId);
        }
        return $result;
    }

    /**
     * Reindex product variants
     *
     * @param int $parentId
     * @return void
     */
    private function reindexVariants(int $parentId): void
    {
        $indexer = $this->indexerRegistry->get(ProductVariantFeedIndexer::INDEXER_ID);
        if (!$indexer->isScheduled()) {
            $indexer->reindexRow($parentId);
        }
    }
}