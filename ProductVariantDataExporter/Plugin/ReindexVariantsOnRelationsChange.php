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
use Magento\ProductVariantDataExporter\Model\Indexer\UpdateChangeLog;
use Psr\Log\LoggerInterface;

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
     * @var UpdateChangeLog
     */
    private $updateChangeLog;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param UpdateChangeLog $updateChangeLog
     * @param LoggerInterface $logger
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        UpdateChangeLog $updateChangeLog,
        LoggerInterface $logger
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->updateChangeLog = $updateChangeLog;
        $this->logger = $logger;
    }

    /**
     * Reindex variants after addition of new relations
     *
     * @param Relation $subject
     * @param Relation $result
     * @param int $parentLinkId
     * @param int[] $childIds
     * @return Relation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddRelations(
        Relation $subject,
        Relation $result,
        int $parentLinkId,
        array $childIds
    ): Relation {
        if (!empty($childIds)) {
            try {
                $this->addCommitCallback($subject, $childIds);
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'Failed to reindex product variants after product relation addition: "%s"',
                    $e->getMessage()
                ));
            }
        }
        return $result;
    }

    /**
     * Reindex variants after removal of relations
     *
     * @param Relation $subject
     * @param Relation $result
     * @param int $parentLinkId
     * @param int[] $childIds
     * @return Relation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRemoveRelations(
        Relation $subject,
        Relation $result,
        int $parentLinkId,
        $childIds
    ): Relation {
        if (!empty($childIds)) {
            // childIds may be a string of 1 element
            $childIds = (array)$childIds;
            try {
                $this->addCommitCallback($subject, $childIds);
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'Failed to reindex product variants after product relation removal: "%s"',
                    $e->getMessage()
                ));
            }
        }
        return $result;
    }

    /**
     * Reindex product variants after commit
     *
     * @param Relation $subject
     * @param array $childIds
     * @return void
     */
    private function addCommitCallback(Relation $subject, array $childIds): void
    {
        $indexer = $this->indexerRegistry->get(ProductVariantFeedIndexer::INDEXER_ID);
        $viewId = $indexer->getViewId();

        if ($indexer->isScheduled()) {
            $subject->addCommitCallback(function () use ($viewId, $childIds) {
                $this->updateChangeLog->execute($viewId, $childIds);
            });
        } else {
            $subject->addCommitCallback(function () use ($indexer, $childIds) {
                $indexer->reindexList($childIds);
            });
        }
    }
}
