<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Query;

use Magento\CatalogInventoryDataExporter\Model\InventoryHelper;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\CatalogInventory\Model\Stock;

/**
 * Gets information about product inventory with MSI enabled
 */
class InventoryData
{
    private ResourceConnection $resourceConnection;
    private CommerceDataExportLoggerInterface $logger;
    private int $defaultStockId;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CommerceDataExportLoggerInterface $logger
     * @param ?InventoryHelper $inventoryHelper
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CommerceDataExportLoggerInterface $logger,
        ?InventoryHelper $inventoryHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $inventoryHelper = $inventoryHelper ?? ObjectManager::getInstance()->get(InventoryHelper::class);
        $this->defaultStockId = $inventoryHelper->isMSIEnabled()
            ? ObjectManager::getInstance()->get(\Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface::class)
                ->getId()
            : Stock::DEFAULT_STOCK_ID;
    }

    /**
     * Get is_salable data from inventory
     *
     * @param array $arguments
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
            $select = $connection->select()
                ->from(
                    ['e' => $this->resourceConnection->getTableName('catalog_product_entity')],
                    ['productId' => 'e.entity_id']
                );

            $stockId = (int)$stock['stock_id'];
            if ($stockId === $this->defaultStockId) {
                // to fix performance issue with `inventory_stock_1` view that doesn't have proper index
                $select->joinInner(
                    ['i' => $this->resourceConnection->getTableName('cataloginventory_stock_status')],
                    'e.entity_id = i.product_id',
                    ['is_in_stock' => 'i.stock_status',  'quantity' => 'i.qty']
                );
            } else {
                $select->joinInner(
                    ['i' => $this->resourceConnection->getTableName(sprintf('inventory_stock_%s', $stockId))],
                    'e.sku = i.sku',
                    ['is_in_stock' => 'i.is_salable', 'i.quantity']
                );
            }

            $select
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
            $union[] = $select;
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
