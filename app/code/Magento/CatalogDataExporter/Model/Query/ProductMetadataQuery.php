<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Product metadata query for catalog data exporter
 */
class ProductMetadataQuery
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
     * @throws \Zend_Db_Select_Exception
     */
    public function getQuery(array $arguments): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['eav' => $this->resourceConnection->getTableName('eav_attribute')], [])
            ->join(
                ['cea' => $this->resourceConnection->getTableName('catalog_eav_attribute')],
                'eav.attribute_id = cea.attribute_id'
            )
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName('store')],
                '1 = 1 AND s.store_id != 0',
                ['storeViewCode' => 's.code']
            )
            ->joinLeft(
                ['eav_label' => $this->resourceConnection->getTableName('eav_attribute_label')],
                'eav.attribute_id = eav_label.attribute_id AND eav_label.store_id = s.store_id'
            )
            ->where('cea.attribute_id IN (?)', $arguments['id'])
            ->columns(
                [
                    'id' => 'eav.attribute_id',
                    'attributeCode' => 'eav.attribute_code',
                    'entityTypeId' => 'eav.entity_type_id',
                    'dataType' => 'eav.backend_type',
                    'validation' => 'eav.frontend_class',
                    'multi' => new Expression(
                        "CASE WHEN eav.frontend_input IN ('multiline', 'multiselect') THEN 1 ELSE 0 END"
                    ),
                    'frontendInput' => 'eav.frontend_input',
                    'label' => $connection->getIfNullSql('eav_label.value', 'eav.frontend_label'),
                    'required' => 'eav.is_required',
                    'unique' => 'eav.is_unique',
                    'global' => 'cea.is_global',
                    'visible' => 'cea.is_visible',
                    'searchable' => 'cea.is_searchable',
                    'filterable' => 'cea.is_filterable',
                    'visibleInCompareList' => 'cea.is_comparable',
                    'visibleInListing' => 'cea.used_in_product_listing',
                    'sortable' => 'cea.used_for_sort_by',
                    'visibleInSearch' => 'cea.is_visible_on_front',
                    'filterableInSearch' => 'cea.is_filterable_in_search',
                    'searchWeight' => 'cea.search_weight',
                    'usedForRules' => 'cea.is_used_for_price_rules',
                    'systemAttribute' => 'eav.is_user_defined',
                ]
            );
    }
}
