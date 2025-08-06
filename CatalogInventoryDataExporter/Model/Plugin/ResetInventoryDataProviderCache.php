<?php
/**
 * Copyright 2025 Adobe
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

use Magento\CatalogDataExporter\Model\Provider\Products;
use Magento\CatalogInventoryDataExporter\Model\Provider\Product\InventoryDataProvider;

/**
 * Plugin for resetting the InventoryDataProvider cache for each new batch during reindex
 */
class ResetInventoryDataProviderCache
{
    private InventoryDataProvider $inventoryDataProvider;

    /**
     * @param InventoryDataProvider $inventoryDataProvider
     */
    public function __construct(
        InventoryDataProvider $inventoryDataProvider
    ) {
        $this->inventoryDataProvider = $inventoryDataProvider;
    }
    /**
     * Reset InventoryDataProvider cache before executing the Products provider to ensure fresh data
     *
     * @param Products $subject
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(Products $subject): void
    {
        $this->inventoryDataProvider->resetCache();
    }
}
