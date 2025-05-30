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

namespace Magento\CatalogInventoryDataExporter\Model\Plugin;

use Magento\CatalogDataExporter\Model\Provider\Product\Buyable as ProductBuyable;
use Magento\CatalogInventoryDataExporter\Model\Provider\Product\InventoryDataProvider;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Plugin for fetching products stock status and marking out of stock products
 */
class Buyable
{
    private LoggerInterface $logger;

    private InventoryDataProvider $inventoryDataProvider;

    /**
     * @param LoggerInterface $logger
     * @param InventoryDataProvider $inventoryDataProvider
     */
    public function __construct(
        LoggerInterface $logger,
        InventoryDataProvider $inventoryDataProvider
    ) {
        $this->logger = $logger;
        $this->inventoryDataProvider = $inventoryDataProvider;
    }
    /**
     * Check stock status after getting buyable product status
     *
     * @param ProductBuyable $subject
     * @param array $result
     *
     * @return array
     *
     * @throws UnableRetrieveData
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(ProductBuyable $subject, array $result)
    {
        try {
            $outOfStock = [];
            foreach ($this->inventoryDataProvider->get($result) as $stockItem) {
                if (!$stockItem['inStock']) {
                    $outOfStock[$this->getKey($stockItem)] = true;
                }
            }
            foreach ($result as &$item) {
                if (isset($outOfStock[$this->getKey($item)])) {
                    $item['buyable'] = false;
                }
            }
        } catch (\Exception $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve "buyable" field: %s', $exception->getMessage()),
                0,
                $exception
            );
        }
        return $result;
    }

    /**
     * @param mixed $stockItem
     * @return string
     */
    private function getKey(mixed $stockItem): string
    {
        return $stockItem['storeViewCode'] . '_' . $stockItem['productId'];
    }
}
