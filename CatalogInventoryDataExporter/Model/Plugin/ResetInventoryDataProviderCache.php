<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Plugin;

use Magento\CatalogDataExporter\Model\Provider\Products;
use Magento\CatalogInventoryDataExporter\Model\Provider\Product\InventoryDataProvider;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

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
     * @param array $arguments
     * @param callable $dataProcessorCallback
     * @param FeedIndexMetadata $metadata
     * @param ? $node
     * @param ? $info
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        Products $subject,
        array $arguments,
        callable $dataProcessorCallback,
        FeedIndexMetadata $metadata,
        $node  =  null,
        $info  =  null
    ): void {
        $this->inventoryDataProvider->resetCache();
    }
}
