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
 * Product variant query builder
 */
class VariantsQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * VariantsQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @return Select
     */
    public function getQuery(array $arguments) : Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCodes = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField($this->getTable('catalog_product_entity'));

        $select = $connection->select()
            ->from(['cpsl' => $this->getTable('catalog_product_super_link')], [])
            ->joinInner(
                ['cpsa' => $this->getTable('catalog_product_super_attribute')],
                'cpsa.product_id = cpsl.parent_id',
                []
            )
            ->joinInner(
                ['cpsal' => $this->getTable('catalog_product_super_attribute_label')],
                'cpsa.product_super_attribute_id = cpsal.product_super_attribute_id',
                []
            )
            ->joinInner(
                ['cpe' => $this->getTable('catalog_product_entity')],
                'cpe.entity_id = cpsl.product_id',
                []
            )
            ->joinInner(
                ['cpeParent' => $this->getTable('catalog_product_entity')],
                sprintf('cpeParent.%1$s = cpsl.parent_id', $joinField),
                []
            )
            ->joinInner(
                ['cpei' => $this->getTable('catalog_product_entity_int')],
                sprintf('cpei.%1$s = cpe.%1$s AND cpsa.attribute_id = cpei.attribute_id', $joinField),
                []
            )
            ->joinInner(
                ['cpip' => $this->getTable('catalog_product_index_price')],
                'cpip.entity_id = cpe.entity_id',
                []
            )
            ->joinInner(
                ['eao' => $this->getTable('eav_attribute_option')],
                'eao.attribute_id = cpsa.attribute_id',
                []
            )
            ->joinInner(
                ['eaov' => $this->getTable('eav_attribute_option_value')],
                'eaov.option_id = cpei.value and eaov.option_id = eao.option_id',
                []
            )
            ->joinInner(
                ['s' => $this->getTable('store')],
                's.website_id = cpip.website_id'
            )
            ->columns(
                [
                    'productId' => 'cpeParent.entity_id',
                    'storeViewCode' => 's.code',
                    'sku' => 'cpe.sku',
                    'price' => 'cpip.price',
                    'finalPrice' => 'cpip.final_price',
                    'name' => 'cpsal.value',
                    'value' => 'eaov.value',
                    'cpei.attribute_id',
                    'optionId' => 'cpei.value'
                ]
            )
            ->where('cpeParent.entity_id IN (?)', $productIds)
            ->where('cpip.customer_group_id = 0')
            ->where('s.code IN (?)', $storeViewCodes)
            ->distinct(true);
        return $select;
    }
}
