<?php
/**
 * Copyright 2023 Adobe
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

use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product low stock data provider
 */
class LowStock
{
    /**
     * @deprecated Use InventoryDataProvider instead
     */
    private ResourceConnection $resourceConnection;

    /**
     * @deprecated Logging will be done in InventoryDataProvider instead
     */
    private LoggerInterface $logger;

    /**
     * @deprecated Use InventoryDataProvider instead
     */
    private CatalogInventoryQuery $catalogInventoryQuery;

    private ScopeConfigInterface $scopeConfig;

    private InventoryDataProvider $inventoryDataProvider;

    /**
     * @param ?ResourceConnection $resourceConnection
     * @param ?CatalogInventoryQuery $catalogInventoryQuery
     * @param ?LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param InventoryDataProvider|null $cachedInventoryDataProvider
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ?ResourceConnection $resourceConnection,
        ?CatalogInventoryQuery $catalogInventoryQuery,
        ?LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        ?InventoryDataProvider $cachedInventoryDataProvider = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->inventoryDataProvider = $cachedInventoryDataProvider
            ?? ObjectManager::getInstance()->get(InventoryDataProvider::class);
    }

    /**
     * Format output
     *
     * @param array $row
     * @param array $thresholds
     * @return array
     */
    private function format(array $row, array $thresholds) : array
    {
        $thresholdQty = $thresholds[$row['storeViewCode']];
        if ($row['quantity'] < $thresholdQty) {
            $lowStock = true;
        } else {
            $lowStock = false;
        }
        $output = [
            'productId' => $row['productId'],
            'storeViewCode' => $row['storeViewCode'],
            'lowStock' => $lowStock,
        ];
        return $output;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values) : array
    {
        $queryArguments = [];
        try {
            $output = [];
            foreach ($values as $value) {
                $queryArguments['storeViewCodes'][] = $value['storeViewCode'];
            }
            $thresholds = $this->getThresholdAmount($queryArguments['storeViewCodes']);
            $inventoryData = $this->inventoryDataProvider->get($values);
            foreach ($inventoryData as $key => $stockItem) {
                $output[$key] = $this->format(
                    $stockItem,
                    $thresholds
                );
            }
        } catch (\Exception $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve LowStock field data: %s', $exception->getMessage()),
                0,
                $exception
            );
        }
        return $output;
    }

    /**
     * Get threshold amount
     *
     * @param array $storeViewCodes
     * @return array
     */
    private function getThresholdAmount(array $storeViewCodes) : array
    {
        $thresholds = [];
        foreach ($storeViewCodes as $storeViewCode) {
            $thresholds[$storeViewCode] = (float) $this->scopeConfig->getValue(
                Configuration::XML_PATH_STOCK_THRESHOLD_QTY,
                ScopeInterface::SCOPE_STORE,
                $storeViewCode
            );
        }
        return $thresholds;
    }
}
