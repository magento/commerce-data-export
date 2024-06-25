<?php
/**
 * Black list for the @see \Magento\Test\Integrity\DependencyTest::testUndeclared()
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
