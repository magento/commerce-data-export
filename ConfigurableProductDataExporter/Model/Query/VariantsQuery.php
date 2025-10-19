<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Query;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Product variant query builder
 */
class VariantsQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @param ?int $statusAttributeId
     * @return Select
     */
    public function getQuery(array $arguments, ?int $statusAttributeId = null) : Select
    {
        $productIds = $arguments['productId'] ?? [];
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName('catalog_product_entity')
        );

        $select = $connection->select()
            ->from(['cpsl' => $this->resourceConnection->getTableName('catalog_product_super_link')], [])
            ->joinInner(
                ['cpsa' => $this->resourceConnection->getTableName('catalog_product_super_attribute')],
                'cpsa.product_id = cpsl.parent_id',
                []
            )
            ->joinInner(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'cpe.entity_id = cpsl.product_id',
                []
            )
            ->joinInner(
                ['cpeParent' => $this->resourceConnection->getTableName('catalog_product_entity')],
                sprintf('cpeParent.%1$s = cpsl.parent_id', $joinField),
                []
            )->join(
                ['product_website' => $this->resourceConnection->getTableName('catalog_product_website')],
                'product_website.product_id = cpe.entity_id',
                []
            )->joinInner(
                ['s' => $this->resourceConnection->getTableName('store')],
                's.website_id = product_website.website_id'
            )
            ->columns(
                [
                    'storeViewCode' => 's.code',
                    'productId' => 'cpeParent.entity_id',
                    'sku' => 'cpe.sku'
                ]
            )
            ->where('cpeParent.entity_id IN (?)', $productIds)
            ->distinct();

        if ($statusAttributeId) {
            $select->joinLeft(
                ['eav' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                \sprintf('cpe.%1$s = eav.%1$s', $joinField) .
                $connection->quoteInto(' AND eav.attribute_id = ?', $statusAttributeId) .
                ' AND eav.store_id = 0',
                []
            )
            ->joinLeft(
                ['eav_store' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                \sprintf('cpe.%1$s = eav_store.%1$s', $joinField) .
                ' AND eav_store.attribute_id = eav.attribute_id' .
                ' AND eav_store.store_id = s.store_id',
                [
                    'status' => new Expression(
                        'IF (eav_store.value_id, eav_store.value, eav.value)'
                    ),
                ]
            )
            ->having('status != ?', Status::STATUS_DISABLED);
        }

        return $select;
    }
}
