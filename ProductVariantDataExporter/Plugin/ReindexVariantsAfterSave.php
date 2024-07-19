<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Plugin;

use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product as ResourceProduct;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\ProductVariantDataExporter\Model\Query\LinkedAttributesQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;
use Magento\ProductVariantDataExporter\Model\Indexer\ProductVariantFeedIndexer;

/**
 * Plugin to trigger reindex on parent products, when a super attribute value is changed on a child product
 */
class ReindexVariantsAfterSave
{
    private ResourceConnection $resourceConnection;
    private LinkedAttributesQuery $linkedAttributesQuery;
    private IndexerRegistry $indexerRegistry;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LinkedAttributesQuery $linkedAttributesQuery
     * @param IndexerRegistry $indexerRegistry
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LinkedAttributesQuery $linkedAttributesQuery,
        IndexerRegistry $indexerRegistry,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->linkedAttributesQuery = $linkedAttributesQuery;
        $this->indexerRegistry = $indexerRegistry;
        $this->logger = $logger;
    }

    /**
     * Reindex parent products on change of child product attribute value
     *
     * @param ResourceProduct $subject
     * @param ResourceProduct $result
     * @param AbstractModel $product
     * @return ResourceProduct
     * @throws \Zend_Db_Statement_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ResourceProduct $subject,
        ResourceProduct $result,
        AbstractModel $product
    ): ResourceProduct {
        try {
            if (\in_array($product->getTypeId(), [Type::TYPE_SIMPLE, Type::TYPE_VIRTUAL], true)) {
                $select = $this->linkedAttributesQuery->getQuery((int)$product->getId());
                $linkedAttributes = $this->resourceConnection->getConnection()->fetchCol($select);
                foreach ($linkedAttributes as $linkAttribute) {
                    if ($product->getOrigData($linkAttribute) !== $product->getData($linkAttribute)) {
                        $this->reindexVariant((int)$product->getId());
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

        return $result;
    }

    /**
     * Reindex product variant
     *
     * @param int $id
     * @return void
     */
    private function reindexVariant(int $id): void
    {
        $indexer = $this->indexerRegistry->get('catalog_data_exporter_product_variants');
        if (!$indexer->isScheduled()) {
            $indexer->reindexRow($id);
        }
    }
}
