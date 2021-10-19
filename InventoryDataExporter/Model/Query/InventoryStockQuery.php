<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Inventory Stock data query builder
 */
class InventoryStockQuery
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
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get query for provider
     *
     * @param array $skus
     * @param string $stockId
     * @param string $sourceField
     * @param string|null $feedField
     * @return Select
     */
    public function getQuery(array $skus, string $stockId, string $sourceField, string $feedField = null) : Select
    {
        $feedField = $feedField ?? $sourceField;
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['isi' => $this->getTable(sprintf('inventory_stock_%s', $stockId))], [])
            ->columns(
                [
                    $feedField => "isi.$sourceField",
                    'sku' => "isi.sku"
                ]
            )
            ->where('isi.sku IN (?)', $skus);
    }
}
