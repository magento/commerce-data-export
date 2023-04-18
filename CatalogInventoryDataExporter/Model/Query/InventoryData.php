<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Query;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
class InventoryData
{
    private ResourceConnection $resourceConnection;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(ResourceConnection $resourceConnection, CommerceDataExportLoggerInterface $logger)
    {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Get is_salable data from inventory
     *
     * @param $arguments
     * @return Select|null
     * @throws \Zend_Db_Select_Exception
     */
    public function get($arguments): ?Select
    {
        $productIds = $arguments['productId'] ?? [];
        $connection = $this->resourceConnection->getConnection();

        $stocks = $connection->select()
            ->from(
                ['channel' => $this->resourceConnection->getTableName('inventory_stock_sales_channel')],
                ['stock_id' => 'channel.stock_id']
            )
            ->joinInner(
                ['w' => $this->resourceConnection->getTableName('store_website')],
                'channel.code = w.code',
                ['website_ids' => ' GROUP_CONCAT(DISTINCT w.website_id)']
            )
            ->where('channel.type = ?', 'website')
            ->group('stock_id');

        $union = [];
        foreach ($stocks->query()->fetchAll() as $stock) {
            $union[] = $connection->select()
                ->from(
                    ['e' => $this->resourceConnection->getTableName('catalog_product_entity')],
                    ['productId' => 'e.entity_id']
                )
                ->joinInner(
                    ['i' => $this->resourceConnection->getTableName(sprintf('inventory_stock_%s', $stock['stock_id']))],
                    'e.sku = i.sku',
                    ['is_in_stock' => 'i.is_salable', 'i.quantity']
                )
                ->joinInner(
                    ['pw' => $this->resourceConnection->getTableName('catalog_product_website')],
                    'pw.product_id = e.entity_id',
                    []
                )
                ->joinCross(
                    ['s' => $this->resourceConnection->getTableName('store')],
                    ['storeViewCode' => 's.code']
                )
                ->where('e.entity_id IN (?)', $productIds)
                ->where('pw.website_id IN (?)', explode(',', $stock['website_ids']))
                ->where('s.website_id IN (?)', explode(',', $stock['website_ids']))
                ->group('e.entity_id')
                ->group('s.store_id');
        }

        if (!$union) {
            $this->logger->error(
                "No stocks found for website sales channel. Cannot obtain stock status."
            );
            return null;
        }
        return $connection->select()->union($union, Select::SQL_UNION_ALL);
    }
}
