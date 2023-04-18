<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Provider\Product;

use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product low stock data provider
 */
class LowStock
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CatalogInventoryQuery
     */
    private $catalogInventoryQuery;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CatalogInventoryQuery $catalogInventoryQuery
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CatalogInventoryQuery $catalogInventoryQuery,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->catalogInventoryQuery = $catalogInventoryQuery;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
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
        if ($row['qty'] < $thresholdQty) {
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
        $connection = $this->resourceConnection->getConnection();
        $queryArguments = [];
        try {
            $output = [];
            foreach ($values as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            $thresholds = $this->getThresholdAmount($queryArguments['storeViewCode']);
            $select = $this->catalogInventoryQuery->getInStock($queryArguments);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[] = $this->format($row, $thresholds);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve stock data');
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
