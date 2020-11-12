<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ResourceProduct;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;
use Magento\ProductVariantDataExporter\Model\Indexer\ProductVariantFeedIndexer;
use Magento\ProductVariantDataExporter\Model\Indexer\UpdateChangeLog;
use Psr\Log\LoggerInterface;

/**
 * Plugin to trigger reindex on product variants upon product deletion
 */
class ReindexVariantsOnDelete
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

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
     * @param ResourceConnection $resourceConnection
     * @param IndexerRegistry $indexerRegistry
     * @param LoggerInterface $logger
     * @param UpdateChangeLog $updateChangeLog
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        IndexerRegistry $indexerRegistry,
        LoggerInterface $logger,
        UpdateChangeLog $updateChangeLog
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->indexerRegistry = $indexerRegistry;
        $this->updateChangeLog = $updateChangeLog;
        $this->logger = $logger;
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
        $ids = [];
        try {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()->from(
                'catalog_product_relation',
                ['parent_id']
            )->where(
                sprintf('(parent_id = "%1$s" OR child_id = "%1$s")', (string)$product->getId())
            );
            $ids = array_filter($connection->fetchCol($select));
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve product relation information ' . $e->getMessage());
        }

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
