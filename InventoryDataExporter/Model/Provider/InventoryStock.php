<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\InventoryDataExporter\Model\Query\InventoryStockQuery;
use Magento\Framework\App\ResourceConnection;

/**
 * Class for getting is salable and qty values.
 */
class InventoryStock
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var InventoryStockQuery
     */
    private $query;

    /**
     * @var string
     */
    private $sourceFieldName;

    /**
     * @var string
     */
    private $feedFieldName;

    /**
     * @param ResourceConnection $resourceConnection
     * @param InventoryStockQuery $query
     * @param string $sourceFieldName
     * @param string $feedFieldName
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        InventoryStockQuery $query,
        string $sourceFieldName,
        string $feedFieldName
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->query = $query;
        $this->sourceFieldName = $sourceFieldName;
        $this->feedFieldName = $feedFieldName;
    }

    /**
     * Getting inventory stock status values.
     *
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values): array
    {
        $connection = $this->resourceConnection->getConnection();
        $skus = [];
        $stockIds = [];
        foreach ($values as $value) {
            $skus[] = $value['sku'];
            $stockIds[] = $value['stockId'];
        }
        $output = [];
        foreach ($stockIds as $stockId) {
            $select = $this->query->getQuery($skus, $stockId, $this->sourceFieldName, $this->feedFieldName);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                //We need it here for correct items mapping during record merge
                $row['stockId'] = $stockId;
                $output[] = $row;
            }
        }
        return $output;
    }
}
