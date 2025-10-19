<?php
/**
 * Black list for the @see \Magento\Test\Integrity\DependencyTest::testUndeclared()
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
return [
    'app/code/Magento/DataExporter/Model/Batch/Feed/Generator.php' => ['Magento\ResourceConnections'],
    'app/code/Magento/DataExporter/Model/Batch/FeedChangeLog/Generator.php' => ['Magento\ResourceConnections'],
    'app/code/Magento/DataExporter/Model/Batch/FeedSource/Generator.php' => ['Magento\ResourceConnections'],
];
