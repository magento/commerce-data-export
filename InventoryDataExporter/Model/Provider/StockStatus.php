<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\DB\Adapter\TableNotFoundException;
use Magento\InventoryDataExporter\Model\Query\InventoryStockQuery;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

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
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param InventoryStockQuery $query
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        InventoryStockQuery $query,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->query = $query;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
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
        $skus = \array_column($values, 'sku');
        $connection = $this->resourceConnection->getConnection();
        $output = [];

        try {
            $select = $this->query->getQuery($skus);
            // $select can be null if no stocks exists except default
            if ($select) {
                $cursor = $connection->query($select);
                while ($row = $cursor->fetch()) {
                    $output[] = $this->fillWithDefaultValues($row);
                }
            }

            $select = $this->query->getQueryForDefaultStock($skus);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[] = $this->fillWithDefaultValues($row);
            }
        } catch (TableNotFoundException $e) {
            $this->logger->warning(
                'StockStatus export warning. Inventory index should be run first. Error: ' . $e->getMessage(). ' '
            );
        } catch (\Throwable $e) {
            $this->logger->error('StockStatus export error: ' . $e->getMessage(), ['exception' => $e]);
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
        if (!isset($row['qty'], $row['isSalable'], $row['sku'], $row['stockId'], $row['manageStock'],
            $row['useConfigManageStock'], $row['backorders'], $row['useConfigBackorders'])) {
            throw new \RuntimeException("missed required field: " . \var_export($row, true));
        }
        // set updated at
        $row['updatedAt'] = $this->dateTime->formatDate(time());
        // set default values
        $row['infiniteStock'] = false;
        $row['qtyForSale'] = $row['qty'];

        return $row;
    }
}
