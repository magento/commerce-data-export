<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Relation;
use Magento\ProductVariantDataExporter\Model\Indexer\ProductVariantFeedIndexer;

/**
 * Plugin to trigger reindex on variants when relations are changed
 */
class ReindexVariantsOnRelationsChange
{
    /**
     * @var ProductVariantFeedIndexer
     */
    private $feedIndexer;

    /**
     * ReindexVariantsOnRelationsChange constructor.
     * @param ProductVariantFeedIndexer $feedIndexer
     */
    public function __construct(
        ProductVariantFeedIndexer $feedIndexer
    ) {
        $this->feedIndexer = $feedIndexer;
    }

    /**
     * Reindex variants after additon of new relations
     *
     * TODO: Add indexOnSchedule conditional and fix mview
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
    ) {
        if (!empty($childIds)) {
            $this->feedIndexer->executeRow($parentId);
        }
        return $result;
    }

    /**
     * Reindex variants after removal of relations
     *
     * TODO: Add indexOnSchedule conditional and fix mview
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
    ) {
        if (!empty($childIds)) {
            $this->feedIndexer->executeRow($parentId);
        }
        return $result;
    }
}
