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

namespace Magento\CatalogInventoryDataExporter\Model\Provider\Product;

use Magento\CatalogInventoryDataExporter\Model\InventoryHelper;
use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryQuery;
use Magento\CatalogInventoryDataExporter\Model\Query\InventoryData;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;

/**
 * Provide inventory stock status data depending on current Inventory Management system
 *
 * Temporary Inventory Data supports 2 Inventory implementations
 * - Legacy Inventory
 * - MSI
 *
 * Check done by verification status of InventoryIndexer module
 */
class InventoryDataProvider
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * Provide inventory data when MSI modules enabled
     *
     * @var InventoryData
     */
    private InventoryData $inventoryData;

    /**
     * @var CatalogInventoryQuery
     */
    private CatalogInventoryQuery $catalogInventoryQuery;

    /**
     * @var InventoryHelper
     */
    private InventoryHelper $inventoryHelper;

    private array $cachedBatch = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param InventoryData $inventoryData
     * @param CatalogInventoryQuery $catalogInventoryQuery
     * @param InventoryHelper|null $inventoryHelper
     */
    public function __construct(
        ResourceConnection    $resourceConnection,
        InventoryData         $inventoryData,
        CatalogInventoryQuery $catalogInventoryQuery,
        ?InventoryHelper $inventoryHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->catalogInventoryQuery = $catalogInventoryQuery;
        $this->inventoryData = $inventoryData;
        $this->inventoryHelper = $inventoryHelper ?? ObjectManager::getInstance()->get(InventoryHelper::class);
    }

    /**
     * @param array $feedItems
     * @return array
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $feedItems): array
    {
        $queryArguments = [];
        $output = [];
        foreach ($feedItems as $value) {
            $queryArguments['productId'][$value['productId']] = $value['productId'];
            $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            // cover case when Inventory Provider doesn't return records if there is no Stock assigned to Website
            $defaultStockValue = $value;
            $defaultStockValue['is_in_stock'] = false;
            $output[$this->getKey($value)] = $this->format($defaultStockValue);
        }
        // If the cached batch is the same as the current request, return the cached data
        if (array_diff_key($output, $this->cachedBatch) || count($this->cachedBatch) !== count($output)) {
            $connection = $this->resourceConnection->getConnection();
            if ($this->inventoryHelper->isMSIEnabled()) {
                $select = $this->inventoryData->get($queryArguments);
            } else {
                $select = $this->catalogInventoryQuery->getInStock($queryArguments);
            }
            if (!$select) {
                return $output;
            }
            $cursor = $connection->query($select);
            while ($stockItem = $cursor->fetch()) {
                $output[$this->getKey($stockItem)] = $this->format($stockItem);
            }
            $this->cachedBatch = $output;
        }
        return $this->cachedBatch;
    }

    /**
     * Format output
     *
     * @param array $row
     * @return array
     */
    private function format(array $row) : array
    {
        return [
            'productId' => $row['productId'],
            'storeViewCode' => $row['storeViewCode'],
            'inStock' => (bool)$row['is_in_stock'],
            'quantity' => $row['quantity'] ?? null
        ];
    }

    /**
     * @param array $item
     * @return string
     */
    private function getKey(array $item): string
    {
        return $item['storeViewCode'] . '_' . $item['productId'];
    }

    /**
     * Reset cache
     *
     * @return void
     */
    public function resetCache(): void
    {
        $this->cachedBatch = [];
    }
}
