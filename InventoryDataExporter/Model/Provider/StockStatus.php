<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

declare(strict_types=1);

namespace Magento\InventoryDataExporter\Model\Provider;

use Magento\DataExporter\Export\DataProcessorInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
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
class StockStatus implements DataProcessorInterface
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
     * @param array $arguments
     * @param callable $dataProcessorCallback
     * @param FeedIndexMetadata $metadata
     * @param null $node
     * @param null $info
     * @return void
     * @throws \Throwable
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(
        array $arguments,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata,
        $node = null,
        $info = null
    ): void {
        // For stock statuses we are operating with product ids
        $ids = \array_column($arguments, 'productId');
        $connection = $this->resourceConnection->getConnection();
        $output = [];

        try {
            $select = $this->query->getQuery($ids);
            // $select can be null if no stocks exists except default
            if ($select) {
                $cursor = $connection->query($select);
                while ($row = $cursor->fetch()) {
                    $output[] = $this->fillWithDefaultValues($row);
                }
            }

            $select = $this->query->getQueryForDefaultStock($ids);
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

        $dataProcessorCallback($this->get($output));
    }

    /**
     * For backward compatibility with existing 3-rd party plugins.
     *
     * @param array $values
     * @return array
     * @deprecated
     * @see self::execute
     */
    public function get(array $values) : array
    {
        return $values;
    }

    /**
     * @param array $row
     * @return array
     */
    private function fillWithDefaultValues(array $row): array
    {
        if (
            !isset(
                $row['qty'],
                $row['isSalable'],
                $row['productId'],
                $row['sku'],
                $row['stockId'],
                $row['manageStock'],
                $row['useConfigManageStock'],
                $row['backorders'],
                $row['useConfigBackorders']
            )
        ) {
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
