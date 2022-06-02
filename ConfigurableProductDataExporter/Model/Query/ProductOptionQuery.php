<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Configurable options data query for product data exporter
 */
class ProductOptionQuery
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
     * @return Select
     */
    public function getQuery(array $arguments): Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCodes = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField(
            $this->resourceConnection->getTableName('catalog_product_entity')
        );
        $select = $connection->select()
            ->from(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                ['productId' => 'product.entity_id']
            )
            ->join(
                ['super_attribute' => $this->resourceConnection->getTableName('catalog_product_super_attribute')],
                sprintf('super_attribute.product_id = product.%s', $joinField),
                [
                    'attribute_id' => 'super_attribute.attribute_id',
                    'position' => 'super_attribute.position'
                ]
            )->join(
                ['eav' => $this->resourceConnection->getTableName('eav_attribute')],
                'eav.attribute_id = super_attribute.attribute_id',
                ['attribute_code' => 'eav.attribute_code']
            )->join(
                ['product_website' => $this->resourceConnection->getTableName('catalog_product_website')],
                'product_website.product_id = product.entity_id',
                []
            )->join(
                ['s' => $this->resourceConnection->getTableName('store')],
                $storeViewCodes
                    ? $connection->quoteInto('s.code IN (?) ', $storeViewCodes)
                     . ' AND s.website_id = product_website.website_id'
                    : 's.store_id != 0' . ' AND s.website_id = product_website.website_id',
                ['storeViewCode' => 's.code']
            )->join(
                ['configurable_link' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                sprintf('configurable_link.parent_id = product.%s', $joinField),
                []
            )->join(
                ['catalog_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'catalog_product_entity.entity_id = configurable_link.product_id',
                []
            )->join(
                ['super_attribute_value' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                sprintf('super_attribute_value.%s = catalog_product_entity.%s', $joinField, $joinField)
                    . ' AND super_attribute_value.attribute_id = super_attribute.attribute_id',
                ['attributeValues' => 'GROUP_CONCAT(DISTINCT super_attribute_value.value)']
            )
            ->joinLeft(
                ['attr_label' => $this->resourceConnection->getTableName('eav_attribute_label')],
                'attr_label.attribute_id = eav.attribute_id and attr_label.store_id = s.store_id',
                [
                    'label' => $connection->getCheckSql(
                        'attr_label.value is NULL',
                        'eav.frontend_label',
                        'attr_label.value'
                    ),
                ]
            )
            ->where('product.entity_id IN (?)', $productIds)
            ->group(['product.entity_id', 's.store_id', 'eav.attribute_code']);
        return $select;
    }
}
