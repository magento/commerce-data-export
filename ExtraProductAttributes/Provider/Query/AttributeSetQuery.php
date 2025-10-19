<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ExtraProductAttributes\Provider\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Product Attribute set query builder
 */
class AttributeSetQuery
{
    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {}

    /**
     * Get query for provider
     *
     * @param array $productIds
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getQuery(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(['p' => $this->resourceConnection->getTableName('catalog_product_entity')], [])
            ->joinInner(
                [
                    'a' => $this->resourceConnection->getTableName('eav_attribute_set'),
                ],
                'a.attribute_set_id = p.attribute_set_id',
                [
                    'productId' => 'p.entity_id',
                    'id' => 'a.attribute_set_id',
                    'name' => 'a.attribute_set_name',
                ]
            )->where('p.entity_id IN (?)', $productIds);
    }
}
