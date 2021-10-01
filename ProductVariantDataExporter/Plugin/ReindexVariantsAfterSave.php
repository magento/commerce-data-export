<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Plugin;

use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product as ResourceProduct;
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
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LinkedAttributesQuery
     */
    private $linkedAttributesQuery;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LinkedAttributesQuery $linkedAttributesQuery
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LinkedAttributesQuery $linkedAttributesQuery,
        IndexerRegistry $indexerRegistry
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->linkedAttributesQuery = $linkedAttributesQuery;
        $this->indexerRegistry = $indexerRegistry;
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
