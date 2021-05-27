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
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                ['productId' => 'cpe.entity_id']
            )
            ->join(
                ['s' => $this->resourceConnection->getTableName('store')],
                's.store_id != 0',
                ['storeViewCode' => 's.code']
            )
            ->join(
                ['psa' => $this->resourceConnection->getTableName('catalog_product_super_attribute')],
                sprintf('psa.product_id = cpe.%s', $joinField),
                [
                    'attribute_id' => 'psa.attribute_id',
                    'super_attribute_id' => 'psa.product_super_attribute_id',
                    'position' => 'psa.position'
                ]
            )
            ->joinLeft(
                ['ald' => $this->resourceConnection->getTableName('catalog_product_super_attribute_label')],
                'ald.product_super_attribute_id = psa.product_super_attribute_id and ald.store_id = 0',
                []
            )
            ->joinLeft(
                ['als' => $this->resourceConnection->getTableName('eav_attribute_label')],
                'als.attribute_id = psa.attribute_id and als.store_id = s.store_id',
                [
                    'label' => new Expression('CASE WHEN als.value IS NULL THEN ald.value ELSE als.value END'),
                    'use_default' => new \Zend_Db_Expr('CASE WHEN als.value IS NULL THEN ald.use_default ELSE "0" END'),
                ]
            )
            ->joinLeft(
                ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                'ea.attribute_id = psa.attribute_id',
                ['attribute_code' => 'ea.attribute_code']
            )
            ->join(
                ['psl' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                sprintf('psl.parent_id = cpe.%1$s', $joinField),
                []
            )
            ->join(
                ['cpc' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'cpc.entity_id = psl.product_id',
                []
            )
            ->join(
                ['cpi' => $this->resourceConnection->getTableName(['catalog_product_entity', 'int'])],
                sprintf(
                    'cpi.%1$s = cpc.%1$s AND psa.attribute_id = cpi.attribute_id AND cpi.store_id = 0',
                    $joinField
                ),
                ['cpi.value']
            )
            ->where('s.code IN (?)', $storeViewCodes)
            ->where('cpe.entity_id IN (?)', $productIds)
            ->order('cpe.entity_id')
            ->order('psa.attribute_id')
            ->order('cpi.value');
        return $select;
    }
}
