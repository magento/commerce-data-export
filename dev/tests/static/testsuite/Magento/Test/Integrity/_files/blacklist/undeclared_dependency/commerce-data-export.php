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
    'app/code/Magento/CatalogExport/Model/CategoryRepository.php' => [
        'Magento\DataExporter'
    ],
    'app/code/Magento/CatalogExport/Model/ProductRepository.php' => [
        'Magento\DataExporter'
    ],
    'app/code/Magento/CatalogExport/Model/ProductVariantRepository.php' => [
        'Magento\DataExporter'
    ],
    'app/code/Magento/CatalogExport/Model/Indexer/EntityIndexerCallback.php' => [
        'Magento\DataExporter',
        'Magento\Framework\MessageQueue'
    ],
    'app/code/Magento/ConfigurationDataExporter/etc/di.xml' => ['Magento\Config'],
    'app/code/Magento/CatalogExport/Model/GenerateDTOs.php' => [
        'Magento\DataExporter',
    ],
    'app/code/Magento/CatalogInventoryDataExporter/Model/Provider/Product/InventoryDataProvider.php' => [
        'Magento\InventoryIndexer',
    ],
    'app/code/Magento/CatalogInventoryDataExporter/Model/Query/InventoryData.php' => [
        'Magento\InventorySales',
    ],
    'app/code/Magento/CatalogInventoryDataExporter/Model/Plugin/StockStatusUpdater.php' => [
        'Magento\InventoryApi',
        'Magento\InventoryMultiDimensionalIndexerApi',
    ],
];
