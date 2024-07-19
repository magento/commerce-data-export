<?php
/**
 * Copyright 2021 Adobe
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
 *
 * Delete products, sources and stocks from DB
 */
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use \Magento\Framework\Registry;
// Delete products
$skusToDelete = [
    'product_without_assigned_source'
];

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);


$currentArea = $registry->registry('isSecureArea');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

foreach ($skusToDelete as $productSku) {
    $productRepository->deleteById($productSku);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', $currentArea);
