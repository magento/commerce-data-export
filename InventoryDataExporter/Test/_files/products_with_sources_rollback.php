<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Delete products, sources and stocks from DB
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\Registry;
// Delete products
$skusToDelete = [
    'product_with_default_stock_only', 'product_with_disabled_manage_stock', 'product_with_enabled_backorders',
    'product_in_EU_stock_with_2_sources', 'product_in_Global_stock_with_3_sources',
    'product_in_default_and_2_EU_sources'
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


// Delete Sources

/** @var ResourceConnection $connection */
$connection = Bootstrap::getObjectManager()->get(ResourceConnection::class);
$connection->getConnection()->delete(
    $connection->getTableName('inventory_source'),
    [
        SourceInterface::SOURCE_CODE . ' IN (?)' => ['eu-1', 'eu-2', 'us-1'],
    ]
);

// Delete Stocks

/** @var StockRepositoryInterface $stockRepository */
$stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);

foreach ([10, 20, 30] as $stockId) {
    try {
        $stockRepository->deleteById($stockId);
    } catch (NoSuchEntityException $e) {
        //Stock already removed
    }
}
