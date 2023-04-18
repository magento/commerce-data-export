<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Provider\Product;

use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryQuery;
use Magento\CatalogInventoryDataExporter\Model\Query\InventoryData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\ModuleList;

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
    private ResourceConnection $resourceConnection;

    /**
     * Provide inventory data when MSI modules enabled
     *
     * @var InventoryData
     */
    private InventoryData $inventoryData;

    /**
     * Provide inventory data when only Legacy Inventory modules enabled
     *
     * @var CatalogInventoryQuery
     */
    private CatalogInventoryQuery $catalogInventoryQuery;

    private ModuleList $moduleList;

    /**
     * @param ResourceConnection $resourceConnection
     * @param InventoryData $inventoryData
     * @param CatalogInventoryQuery $catalogInventoryQuery
     * @param ModuleList $moduleList
     */
    public function __construct(
        ResourceConnection    $resourceConnection,
        InventoryData         $inventoryData,
        CatalogInventoryQuery $catalogInventoryQuery,
        ModuleList $moduleList
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->inventoryData = $inventoryData;
        $this->catalogInventoryQuery = $catalogInventoryQuery;
        $this->moduleList = $moduleList;
    }

    /**
     * @param array $feedItems
     * @return array
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

        $connection = $this->resourceConnection->getConnection();
        if ($this->isMSIEnabled()) {
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

        return $output;
    }

    /**
     * @return bool
     */
    private function isMSIEnabled(): bool
    {
        return $this->moduleList->getOne('Magento_InventoryIndexer') !== null;
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
        ];
    }

    /**
     * @param $item
     * @return string
     */
    private function getKey($item): string
    {
        return $item['productId'] . '-' . $item['storeViewCode'];
    }
}
