<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductInterfaceFactory $productFactory */
$productFactory = $objectManager->get(ProductInterfaceFactory::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$productRepository->cleanCache();

$stockData = [
    'product_without_assigned_source' => [
        'qty' => 8.5,
        'is_in_stock' => true,
        'manage_stock' => true,
    ],
];

foreach ($stockData as $sku => $productStockData) {
    $product = $productFactory->create();
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setAttributeSetId(4)
        ->setName('Simple Product ' . $sku)
        ->setSku($sku)
        ->setPrice(10)
        ->setStockData($productStockData)
        ->setStatus(Status::STATUS_ENABLED);
    $productRepository->save($product);
}
