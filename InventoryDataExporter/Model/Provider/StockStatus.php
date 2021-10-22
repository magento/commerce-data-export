<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryDataExporter\Model\Query\InventoryStockQuery;
use Psr\Log\LoggerInterface;

/**
 * Get inventory stock statuses
 * Fulfill fields for StockItemStatus record:
 *  [
 *    stockId,
 *    qty,
 *    isSalable,
 *    sku
 * ]
 * ]
 */
class StockStatus
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param InventoryStockQuery $query
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        InventoryStockQuery $query,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->query = $query;
        $this->logger = $logger;
    }

    /**
     * Getting inventory stock statuses.
     *
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    public function get(array $values): array
    {
        $productIds = \array_column($values, 'productId');
        $connection = $this->resourceConnection->getConnection();
        $output = [];

        try {
            $select = $this->query->getQuery($productIds);
            $cursor = $connection->query($select);
            $processedIds = [];
            while ($row = $cursor->fetch()) {
                $processedIds[] = $row['productId'];
                $output[] = $this->fillWithDefaultValues($row);
            }

            $select = $this->query->getQueryForDefaultStock(\array_diff($productIds, $processedIds));
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[] = $this->fillWithDefaultValues($row);
            }

        } catch (\Throwable $e) {
            $this->logger->error("StockStatus export error: " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }

        return $output;
    }

    /**
     * @param array $row
     * @return array
     */
    private function fillWithDefaultValues(array $row): array
    {
        if (!isset($row['qty'], $row['isSalable'], $row['sku'], $row['productId'], $row['stockId'], $row['manageStock'],
            $row['useConfigManageStock'], $row['backorders'], $row['useConfigBackorders'])) {
            throw new \RuntimeException("missed required field: " . \var_export($row, true));
        }
        $row['id'] = StockStatusIdBuilder::build($row);
        // set default values
        $row['infiniteStock'] = false;
        $row['qtyForSale'] = $row['qty'];

        return $row;
    }
}
