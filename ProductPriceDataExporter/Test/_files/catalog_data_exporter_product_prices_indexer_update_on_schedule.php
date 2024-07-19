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

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry */
$indexerRegistry = $objectManager->create(\Magento\Framework\Indexer\IndexerRegistry::class);
$indexer = $indexerRegistry->get('catalog_data_exporter_product_prices');
$indexer->setScheduled(true);
