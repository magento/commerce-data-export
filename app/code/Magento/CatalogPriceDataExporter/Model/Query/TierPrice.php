<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

class TierPrice
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
     * Retrieve query for tier prices.
     *
     * @param array $valueIds
     *
     * @return Select
     */
    public function getQuery(array $valueIds): Select
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(['cpetp' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')], [])
            ->columns(
                [
                    'value_id' => 'cpetp.value_id',
                    'group_price_type' => new Expression(
                        'CASE WHEN cpetp.percentage_value IS NOT NULL THEN "percent" ELSE "fixed" END'
                    ),
                    'value' => new Expression(
                        'CASE WHEN cpetp.percentage_value IS NOT NULL THEN cpetp.percentage_value ELSE cpetp.value END'
                    ),
                ]
            );

        if (!empty($valueIds)) {
            $select->where('cpetp.value_id in (?)', $valueIds);
        }

        return $select;
    }
}
