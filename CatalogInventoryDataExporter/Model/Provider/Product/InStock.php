<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
            foreach ($this->inventoryDataProvider->get($values) as $key => $stockItem) {
                $output[$key] = $stockItem;
            }
        } catch (\Exception $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve InStock field data: %s', $exception->getMessage()),
                0,
                $exception
            );
        }
        return $output;
    }
}
