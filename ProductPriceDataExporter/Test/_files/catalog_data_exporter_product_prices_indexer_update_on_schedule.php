<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry */
$indexerRegistry = $objectManager->create(\Magento\Framework\Indexer\IndexerRegistry::class);
$indexer = $indexerRegistry->get('catalog_data_exporter_product_prices');
$indexer->setScheduled(true);
