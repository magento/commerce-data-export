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

/**
 * Products tier prices query provider class
 */
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
     * @param array $ids
     * @param int|null $scopeId
     *
     * @return Select
     */
    public function getQuery(array $ids, ?int $scopeId): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $joinField = $connection->getAutoIncrementField($productEntityTable);

        $select = $connection->select()
            ->from(['cpe' => $productEntityTable], [])
            ->join(
                ['cpetp' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')],
                \sprintf('cpe.%1$s = cpetp.%1$s', $joinField),
                []
            )
            ->columns(
                [
                    'entity_id' => 'cpe.entity_id',
                    'qty' => 'cpetp.qty',
                    'scope_id' => 'cpetp.website_id',
                    'customer_group_id' => new Expression(
                        'CASE WHEN cpetp.all_groups = 1 THEN NULL ELSE cpetp.customer_group_id END'
                    ),
                    'group_price_type' => new Expression(
                        'CASE WHEN cpetp.percentage_value IS NOT NULL THEN "percent" ELSE "fixed" END'
                    ),
                    'value' => new Expression(
                        'CASE WHEN cpetp.percentage_value IS NOT NULL THEN cpetp.percentage_value ELSE cpetp.value END'
                    ),
                ]
            )
            ->where('cpe.entity_id in (?)', $ids);

        if (null !== $scopeId) {
            $select->where('cpetp.website_id = ?', $scopeId);
        }

        return $select;
    }
}
