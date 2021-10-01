<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Build Select object to fetch configurable product variant option values
 */
class ProductVariantsQuery
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
     * @param array $parentIds
     * @return Select
     */
    public function getQuery(array $parentIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName('catalog_product_entity')
        );

        $subSelect = $connection->select()
            ->from(
                ['cpsa' => $this->resourceConnection->getTableName('catalog_product_super_attribute')],
                ['attribute_id']
            )
            ->where(\sprintf('cpsa.product_id IN (cpep.%s)', $joinField));
        $select = $connection->select()
            ->from(
                ['cpec' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->joinInner(
                ['cpsl' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                \sprintf('cpsl.product_id = cpec.entity_id'),
                []
            )
            ->joinInner(
                ['cpep' => $this->resourceConnection->getTableName('catalog_product_entity')],
                \sprintf('cpep.%s = cpsl.parent_id', $joinField),
                []
            )
            ->joinInner(
                ['cpei' => $this->resourceConnection->getTableName(['catalog_product_entity', 'int'])],
                \sprintf(
                    'cpei.%1$s = cpec.%1$s AND cpei.attribute_id IN (%2$s)',
                    $joinField,
                    $subSelect->assemble()
                ),
                []
            )
            ->joinInner(
                ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                'ea.attribute_id = cpei.attribute_id',
                []
            )
            ->joinLeft(
                ['option_value' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                'option_value.option_id = cpei.value',
                []
            )
            ->columns(
                [
                    'parentId' => 'cpep.entity_id',
                    'childId' => 'cpec.entity_id',
                    'attributeId' => 'cpei.attribute_id',
                    'attributeCode' => 'ea.attribute_code',
                    'optionValueId' => 'cpei.value',
                    'productSku' => 'cpec.sku',
                    'parentSku' => 'cpep.sku',
                    'optionLabel' => 'option_value.value'
                ]
            )
            ->where('cpec.entity_id IN (?)', $parentIds);

        return $select;
    }
}
