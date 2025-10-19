<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Indexer\Model\Indexer;

$objectManager = Bootstrap::getObjectManager();
$indexer = $objectManager->create(Indexer::class);
$indexer->load('catalog_data_exporter_products');
$indexer->reindexAll();
