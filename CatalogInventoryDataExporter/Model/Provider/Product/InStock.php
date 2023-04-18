<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
