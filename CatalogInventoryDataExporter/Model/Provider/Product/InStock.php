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

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product in stock data provider
 */
class InStock
{
    private LoggerInterface $logger;

    private InventoryDataProvider $inventoryDataProvider;

    /**
     * @param LoggerInterface $logger
     * @param InventoryDataProvider $cachedInventoryDataProvider
     */
    public function __construct(
        LoggerInterface $logger,
        InventoryDataProvider $cachedInventoryDataProvider
    ) {
        $this->logger = $logger;
        $this->inventoryDataProvider = $cachedInventoryDataProvider;
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
        try {
            $output = [];
            foreach ($this->inventoryDataProvider->get($values) as $stockItem) {
                $output[] = $stockItem;
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve stock data');
        }
        return $output;
    }
}
