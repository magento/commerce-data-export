<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Plugin;

use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product as ResourceProduct;
use Magento\ConfigurableProductDataExporter\Plugin\Query\LinkedAttributesQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;
use Magento\ProductVariantDataExporter\Model\Indexer\ProductVariantFeedIndexer;

/**
 * Plugin to trigger reindex on parent products, when a super attribute value is changed on a child product
 */
class ReindexVariants
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
     * @var ProductVariantFeedIndexer
     */
    private $feedIndexer;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LinkedAttributesQuery $linkedAttributesQuery
     * @param ProductVariantFeedIndexer $feedIndexer
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LinkedAttributesQuery $linkedAttributesQuery,
        ProductVariantFeedIndexer $feedIndexer
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->linkedAttributesQuery = $linkedAttributesQuery;
        $this->feedIndexer = $feedIndexer;
    }

    /**
     * Reindex parent product on change of super attribute value.
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
        $type = $product->getTypeId();
        if ($type === Type::TYPE_SIMPLE || $type === Type::TYPE_VIRTUAL) {
            $select = $this->linkedAttributesQuery->getQuery((int)$product->getId());
            $connection = $this->resourceConnection->getConnection();
            $configurableLinks = $connection->query($select)->fetchAll();
            $changedConfigurableIds = [];
            foreach ($configurableLinks as $link) {
                if ($product->getOrigData($link['attributeCode']) !== $product->getData($link['attributeCode'])) {
                    $changedConfigurableIds[] = $link['parentId'];
                }
            }
            if (!empty($changedConfigurableIds)) {
                $this->feedIndexer->executeList(array_filter($changedConfigurableIds));
            }
        }
        return $result;
    }
}
