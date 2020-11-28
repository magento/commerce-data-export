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
     * Retrieve query for tier price.
     *
     * @param array $queryData
     *
     * @return Select
     */
    public function getQuery(array $queryData): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $joinField = $connection->getAutoIncrementField($productEntityTable);

        return $connection->select()
            ->from(['cpe' => $productEntityTable], [])
            ->join(
                ['cpetp' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')],
                \sprintf('cpe.%1$s = cpetp.%1$s', $joinField),
                []
            )
            ->columns(
                [
                    'group_price_type' => new Expression(
                        'CASE WHEN cpetp.percentage_value IS NOT NULL THEN "percent" ELSE "fixed" END'
                    ),
                    'value' => new Expression(
                        'CASE WHEN cpetp.percentage_value IS NOT NULL THEN cpetp.percentage_value ELSE cpetp.value END'
                    ),
                ]
            )
            ->where('cpe.entity_id = ?', $queryData['entity_id'])
            ->where('cpetp.website_id = ?', $queryData['scope_id'])
            ->where('cpetp.customer_group_id = ?', $queryData['customer_group_id'])
            ->where('cpetp.qty = ?', $queryData['qty'])
            ->where('cpetp.all_groups = ?', $queryData['all_groups']);
    }
}
