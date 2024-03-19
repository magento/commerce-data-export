<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Provider\Product;

use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryStockQueryInterface;
use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryQuery;
use Magento\CatalogInventoryDataExporter\Model\Query\InventoryData;
use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryStockQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\App\ObjectManager;

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
     * @deprecated Not used anymore. Left for BC
     * @see \Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryStockQueryInterface
     * @var InventoryData
     */
    private InventoryData $inventoryData;

    /**
     * @deprecated Not used anymore. Left for BC
     * @see \Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryStockQueryInterface
     * @var CatalogInventoryQuery
     */
    private CatalogInventoryQuery $catalogInventoryQuery;

    /**
     * Provide inventory data
     *
     * @var CatalogInventoryStockQueryInterface
     */
    private CatalogInventoryStockQueryInterface $catalogInventoryStockQuery;

    /**
     * @deprecated Not used anymore. Left for BC
     * @see \Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryStockQueryInterface
     * @var ModuleList
     */
    private ModuleList $moduleList;

    /**
     * @param ResourceConnection $resourceConnection
     * @param InventoryData $inventoryData
     * @param CatalogInventoryQuery $catalogInventoryQuery
     * @param ModuleList $moduleList
     * @param CatalogInventoryStockQueryInterface|null $catalogInventoryStockQuery
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ResourceConnection    $resourceConnection,
        InventoryData         $inventoryData,
        CatalogInventoryQuery $catalogInventoryQuery,
        ModuleList $moduleList,
        ?CatalogInventoryStockQueryInterface $catalogInventoryStockQuery
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->inventoryData = $inventoryData;
        $this->catalogInventoryQuery = $catalogInventoryQuery;
        $this->moduleList = $moduleList;
        $this->catalogInventoryStockQuery = $catalogInventoryStockQuery
            ?? ObjectManager::getInstance()->get(CatalogInventoryStockQuery::class);
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

        $connection = $this->resourceConnection->getConnection();
        $select = $this->catalogInventoryStockQuery->getInStock($queryArguments);
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
     * @param array $item
     * @return string
     */
    private function getKey(array $item): string
    {
        return $item['productId'] . '-' . $item['storeViewCode'];
    }
}
