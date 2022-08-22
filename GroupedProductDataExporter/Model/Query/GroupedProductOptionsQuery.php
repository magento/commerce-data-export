<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GroupedProductDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Build Select object to fetch grouped product options
 */
class GroupedProductOptionsQuery
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
     * @param int[] $productIds
     * @param string $storeViewCode
     *
     * @return Select
     */
    public function getQuery(array $productIds, string $storeViewCode) : Select
    {
        $connection = $this->resourceConnection->getConnection();
        $catalogProductTable = $this->resourceConnection->getTableName('catalog_product_entity');

        return $connection->select()
            ->from(
                [
                    'main_table' => $this->resourceConnection->getTableName('catalog_product_link'),
                ],
                []
            )
            ->join(
                [
                    's' => $this->resourceConnection->getTableName('store'),
                ],
                $connection->quoteInto('s.code = ?', $storeViewCode),
                []
            )
            ->join(
                [
                    'cpe' => $catalogProductTable,
                ],
                \sprintf('cpe.%1$s = main_table.product_id', $connection->getAutoIncrementField($catalogProductTable)),
                []
            )
            ->where('cpe.entity_id IN (?)', $productIds)
            ->columns([
                'store_view_code' => 's.code',
                'product_id' => 'cpe.entity_id',
            ]);
    }
}
