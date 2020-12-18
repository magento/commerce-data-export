<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Products base / special prices query provider class
 */
class ProductPrice
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
     * Retrieve query for product price.
     *
     * @param array $ids
     * @param int $scopeId
     * @param array $attributes
     * @param int|null $lastKnownId
     * @param int|null $batchSize
     * @return Select
     */
    public function getQuery(array $ids, int $scopeId, array $attributes, ?int $lastKnownId = 0, ?int $batchSize = null): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $joinField = $connection->getAutoIncrementField($productEntityTable);
        $select = $connection->select()
            ->from(['cpe' => $productEntityTable], [])
            ->join(
                ['cped' => $this->resourceConnection->getTableName(['catalog_product_entity', 'decimal'])],
                \sprintf('cpe.%1$s = cped.%1$s', $joinField),
                []
            )
            ->join(
                ['eav' => $this->resourceConnection->getTableName('eav_attribute')],
                'cped.attribute_id = eav.attribute_id',
                []
            )
            ->columns(
                [
                    'entity_id' => 'cpe.entity_id',
                    'attribute_code' => 'eav.attribute_code',
                    'value' => 'cped.value',
                ]
            )
            ->where('eav.attribute_code IN (?)', $attributes)
            ->where('cped.store_id = ?', $scopeId)
            ->where('cpe.entity_id > ?', $lastKnownId)
            ->order('cpe.entity_id');

        if ($batchSize !== null) {
            $select->limit($batchSize);
        }
        if (!empty($ids)) {
            $select->where('cpe.entity_id IN (?)', $ids);
        }

        return $select;
    }
}
