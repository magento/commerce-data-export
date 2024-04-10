<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Class CatalogInventoryQuery
 *
 * Gets information about product inventory
 */
class CatalogInventoryQuery
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
     * Get table name
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get query with information about in_stock status
     *
     * @param array $arguments
     * @return Select
     */
    public function getInStock(array $arguments) : Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCodes = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['cpe' => $this->getTable('catalog_product_entity')], '')
            ->joinCross(
                ['s' => $this->getTable('store')],
                ''
            )
            ->joinInner(
                ['csi' => $this->getTable('cataloginventory_stock_status')],
                "cpe.entity_id = csi.product_id",
                ''
            )
            ->columns(
                [
                    'productId' => 'csi.product_id',
                    'storeViewCode' => 's.code',
                    'qty' => 'csi.qty',
                    'is_in_stock' => 'csi.stock_status'
                ]
            )
            ->where('s.code IN (?)', $storeViewCodes)
            ->where('csi.product_id IN (?)', $productIds);
        return $select;
    }
}
