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

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionFactory;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
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

// Create simple product 1
$product1 = $productFactory->create();
$product1->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product 1')
    ->setSku('simple_product_parent_product_test_1')
    ->setPrice(100)
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product1);

// Create simple product 2
$product2 = $productFactory->create();
$product2->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product 2')
    ->setSku('simple_product_parent_product_test_2')
    ->setPrice(100.10)
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product2);

// Create virtual product
$productVirtual = $productFactory->create();
$productVirtual->setTypeId(Type::TYPE_VIRTUAL)
    ->setAttributeSetId(4)
    ->setName('Virtual Product')
    ->setSku('virtual_product_parent_product_test')
    ->setPrice(100.10)
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($productVirtual);

// Create a product without any parent
$productWithNoParent = $productFactory->create();
$productWithNoParent->setTypeId(Type::TYPE_VIRTUAL)
    ->setAttributeSetId(4)
    ->setName('Simple Product With No Parent')
    ->setSku('simple_product_with_no_parent_test')
    ->setPrice(100.10)
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($productWithNoParent);
