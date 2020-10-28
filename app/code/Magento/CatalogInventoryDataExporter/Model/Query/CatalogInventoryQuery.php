<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;


class CatalogInventoryQuery
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * CatalogInventoryQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * @param array $arguments
     * @return Select
     */
    public function getInStock(array $arguments) : Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCodes = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['cpe' => $this->getTable('catalog_product_entity')])
            ->joinCross(
                ['s' => $this->getTable('store')]
            )
            ->joinInner(
                ['csi' => $this->getTable('cataloginventory_stock_item')],
                "cpe.entity_id = csi.product_id"
            )
            ->columns(
                [
                    'productId' => 'csi.product_id',
                    'storeViewCode' => 's.code'
                ]
            )
            ->where('s.code IN (?)', $storeViewCodes)
            ->where('csi.product_id IN (?)', $productIds);
        return $select;
    }
}
