<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query\Eav;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Generic query that fetches EAV attribute metadata for a given catalog entity type
 * (catalog_product / catalog_category). The entity type code is injected via DI.
 */
class EavAttributeMetadataQuery
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var string
     */
    private string $entityTypeCode;
    private bool $includeOnlyUserDefinedAttributes;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $entityTypeCode
     * @param bool $includeOnlyUserDefinedAttributes
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $entityTypeCode = 'catalog_product',
        bool $includeOnlyUserDefinedAttributes = false
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->entityTypeCode = $entityTypeCode;
        $this->includeOnlyUserDefinedAttributes = $includeOnlyUserDefinedAttributes;
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @param int[] $scopeIds Store view id(s) to extract; empty means all store views
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getQuery(array $arguments, array $scopeIds = []): Select
    {
        $connection = $this->resourceConnection->getConnection();

        $scopeIds = array_values(array_filter(array_map('intval', $scopeIds)));
        $storeCondition = empty($scopeIds)
            ? 's.store_id != 0'
            : $connection->quoteInto('s.store_id IN (?)', $scopeIds);

        $select = $connection->select()
            ->from(['eav' => $this->resourceConnection->getTableName('eav_attribute')], [])
            ->join(
                ['eav_type' => $this->resourceConnection->getTableName('eav_entity_type')],
                sprintf(
                    'eav_type.entity_type_code = "%s" AND eav.entity_type_id = eav_type.entity_type_id',
                    $this->entityTypeCode
                ),
                []
            )
            ->join(
                ['cea' => $this->resourceConnection->getTableName('catalog_eav_attribute')],
                'eav.attribute_id = cea.attribute_id',
                []
            )
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName('store')],
                $storeCondition,
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
                    'visible' => 'cea.is_visible_on_front',
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
        if ($this->includeOnlyUserDefinedAttributes) {
            $select->where('eav.is_user_defined = 1');
        }
        return $select;
    }
}
