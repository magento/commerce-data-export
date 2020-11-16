<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Relation;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
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
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param UpdateChangeLog $updateChangeLog
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        UpdateChangeLog $updateChangeLog,
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->updateChangeLog = $updateChangeLog;
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
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
                $parentId = $this->getProductEntityId($parentLinkId);
                $this->addCommitCallback($subject, (int)$parentId);
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
        array $childIds
    ): Relation {
        if (!empty($childIds)) {
            try {
                $parentId = $this->getProductEntityId($parentLinkId);
                $this->addCommitCallback($subject, (int)$parentId);
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
     * @param int $id
     * @return void
     */
    private function addCommitCallback(Relation $subject, int $id): void
    {
        $indexer = $this->indexerRegistry->get(ProductVariantFeedIndexer::INDEXER_ID);
        $viewId = $indexer->getViewId();

        if ($indexer->isScheduled()) {
            $subject->addCommitCallback(function () use ($viewId, $id) {
                $this->updateChangeLog->execute($viewId, [$id]);
            });
        } else {
            $subject->addCommitCallback(function () use ($indexer, $id) {
                $indexer->reindexRow($id);
            });
        }
    }

    /**
     * Get product entity id from product link id
     *
     * @param int $linkId
     * @return int
     * @throws \Exception
     */
    private function getProductEntityId(int $linkId): int
    {
        if (($linkField = $this->getProductEntityLinkField()) !== 'entity_id') {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()->from(
                ['cpe' => $catalogProductTable = $this->resourceConnection->getTableName('catalog_product_entity')],
                ['entity_id']
            )->where(
                sprintf('%1$s = ?', $linkField),
                $linkId
            );
            return (int)$connection->fetchOne($select);
        }
        return $linkId;
    }

    /**
     * Get product entity link field
     *
     * @return string
     * @throws \Exception
     */
    private function getProductEntityLinkField(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }
}
