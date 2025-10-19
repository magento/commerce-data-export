<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Query obtains attribute option values used in configurable products.
 */
class ProductOptionValueQuery
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
        $storeViewCodes = $arguments['storeViewCode'] ?? [];
        $connection = $this->resourceConnection->getConnection();
        $attributeIds = $arguments['attributes'] ?? [];

        $select = $connection->select()
            ->from(
                ['eao' => $this->resourceConnection->getTableName('eav_attribute_option')],
                [
                    'attribute_id' => 'eao.attribute_id',
                    'optionId' => 'eao.option_id',
                    'sortOrder' => 'eao.sort_order'
                ]
            )
            ->join(
                ['s' => $this->resourceConnection->getTableName('store')],
                's.store_id != 0',
                ['storeViewCode' => 's.code']
            )
            ->joinLeft(
                ['ovd' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                'ovd.option_id = eao.option_id AND ovd.store_id = 0',
                []
            )
            ->joinLeft(
                ['ovs' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                'ovs.option_id = eao.option_id AND ovs.store_id = s.store_id',
                [
                    'label' => new Expression('CASE WHEN ovs.value IS NULL THEN ovd.value ELSE ovs.value END'),
                ]
            )
            ->joinLeft(
                ['aod' => $this->resourceConnection->getTableName('eav_attribute_option_swatch')],
                'aod.option_id = eao.option_id AND aod.store_id = 0',
                []
            )
            ->joinLeft(
                ['aos' => $this->resourceConnection->getTableName('eav_attribute_option_swatch')],
                'aos.option_id = eao.option_id AND aos.store_id = s.store_id',
                [
                    'swatchValue' => new Expression('CASE WHEN aos.value IS NULL THEN aod.value ELSE aos.value END'),
                    'swatchType' => new Expression('CASE WHEN aos.value IS NULL THEN aod.type ELSE aos.type END'),
                ]
            )
            ->where('eao.attribute_id IN (?)', $attributeIds);

        if ($storeViewCodes) {
            $select->where('s.code IN (?)', $storeViewCodes);
        }
        return $select;
    }
}
