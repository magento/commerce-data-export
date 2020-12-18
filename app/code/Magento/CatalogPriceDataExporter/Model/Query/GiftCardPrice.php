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
 * Gift card prices query provider class
 */
class GiftCardPrice
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
     *
     * @return Select
     */
    public function getQuery(array $ids, int $scopeId, array $attributes): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $joinField = $connection->getAutoIncrementField($productEntityTable);

        return $connection->select()
            ->from(['cpe' => $productEntityTable], [])
            ->join(
                ['mga' => $this->resourceConnection->getTableName('magento_giftcard_amount')],
                \sprintf('cpe.%1$s = mga.%1$s', $joinField),
                []
            )
            ->join(
                ['eav' => $this->resourceConnection->getTableName('eav_attribute')],
                'mga.attribute_id = eav.attribute_id',
                []
            )
            ->columns(
                [
                    'entity_id' => 'cpe.entity_id',
                    'attribute_code' => 'eav.attribute_code',
                    'value' => 'mga.value',
                ]
            )
            ->where('eav.attribute_code IN (?)', $attributes)
            ->where('mga.website_id = ?', $scopeId)
            ->where('cpe.entity_id IN (?)', $ids);
    }
}
