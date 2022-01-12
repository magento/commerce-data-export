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
 * Attribute options query for catalog data exporter
 */
class AttributeOptionsQuery
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
     * @param array $arguments
     * @param string $storeViewCode
     * @return Select
     */
    public function getQuery(array $arguments, string $storeViewCode): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['eav_option' => $this->resourceConnection->getTableName('eav_attribute_option')], [])
            ->joinInner(
                ['s' => $this->resourceConnection->getTableName('store')],
                $connection->quoteInto('s.code = ?', $storeViewCode),
                ['storeViewCode' => 's.code']
            )
           ->joinInner(
                ['eav_option_value_default' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                'eav_option.option_id = eav_option_value_default.option_id AND eav_option_value_default.store_id = 0',
                []
            )
            ->joinLeft(
                ['eav_option_value_store' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                'eav_option.option_id = eav_option_value_store.option_id AND eav_option_value_store.store_id = s.store_id',
                []
            )
            ->where('eav_option.attribute_id IN (?)', $arguments)
            ->columns(
                [
                    'id' => 'eav_option.attribute_id',
                    'attributeOptions' => new Expression('CASE WHEN eav_option_value_store.value IS NULL THEN eav_option_value_default.value ELSE eav_option_value_store.value END'),
                ]
            );
    }
}
