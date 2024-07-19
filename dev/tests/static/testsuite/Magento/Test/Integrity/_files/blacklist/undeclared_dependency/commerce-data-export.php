<?php
/**
 * Black list for the @see \Magento\Test\Integrity\DependencyTest::testUndeclared()
 *
 * Copyright 2024 Adobe
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
return [
    'app/code/Magento/ConfigurationDataExporter/Model/ConfigExportCallback.php' => ['Magento\Framework\MessageQueue'],
    'app/code/Magento/ConfigurationDataExporter/Model/FullExportProcessor.php' => ['Magento\Store'],
    'app/code/Magento/ConfigurationDataExporter/Observer/ConfigChange.php' => ['Magento\Store','Magento\Config'],
    'app/code/Magento/ConfigurationDataExporter/Plugin/ConfigUpdateExport.php' => ['Magento\Config'],
    'app/code/Magento/ConfigurationDataExporter/etc/di.xml' => ['Magento\Config'],
    'app/code/Magento/CatalogInventoryDataExporter/Model/Provider/Product/InventoryDataProvider.php' => [
        'Magento\InventoryIndexer',
    ],
    'app/code/Magento/CatalogInventoryDataExporter/Model/InventoryHelper.php' => ['Magento\InventoryIndexer'],
    'app/code/Magento/CatalogInventoryDataExporter/Model/Query/InventoryData.php' => [
        'Magento\InventorySales',
        'Magento\InventoryCatalogApi',
    ],
    'app/code/Magento/CatalogInventoryDataExporter/Model/Plugin/StockStatusUpdater.php' => [
        'Magento\InventoryApi',
        'Magento\InventoryMultiDimensionalIndexerApi',
    ],
    'app/code/Magento/DataExporter/Model/Batch/Feed/Generator.php' => ['Magento\ResourceConnections'],
    'app/code/Magento/DataExporter/Model/Batch/FeedChangeLog/Generator.php' => ['Magento\ResourceConnections'],
    'app/code/Magento/DataExporter/Model/Batch/FeedSource/Generator.php' => ['Magento\ResourceConnections'],
    'app/code/Magento/CatalogInventoryDataExporter/Model/InventoryHelper.php'  => ['Magento\InventoryIndexer']
];
