<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Query;

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
            ->where(\sprintf('cpsa.product_id IN (cpe.%s)', $joinField));
        $select = $connection->select()
            ->from(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )
            ->joinInner(
                ['cpsl' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                \sprintf('cpsl.parent_id = cpe.%s', $joinField),
                []
            )
            ->joinInner(
                ['cpec' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'cpec.entity_id = cpsl.product_id',
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
            ->columns(
                [
                    'parentId' => 'cpe.entity_id',
                    'childId' => 'cpec.entity_id',
                    'attributeId' => 'cpei.attribute_id',
                    'attributeCode' => 'ea.attribute_code',
                    'attributeValue' => 'cpei.value'
                ]
            )
            ->where('cpe.entity_id IN (?)', $parentIds);
        return $select;
    }
}
