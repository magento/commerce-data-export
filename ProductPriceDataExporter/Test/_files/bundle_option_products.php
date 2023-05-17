<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductInterfaceFactory $productFactory */
$productFactory = $objectManager->get(ProductInterfaceFactory::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$defaultWebsiteId = $websiteRepository->get('base')->getId();
$product1 = $productFactory->create();
$product1->setAttributeSetId(4)
    ->setName('Simple Product - Bundle Option 1')
    ->setSku('simple_option_1')
    ->setPrice(10)
    ->setWebsiteIds([$defaultWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product1);

$product2 = $productFactory->create();
$product2->setAttributeSetId(4)
    ->setName('Simple Product - Bundle Option 2')
    ->setSku('simple_option_2')
    ->setPrice(20)
    ->setWebsiteIds([$defaultWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product2);

$product3 = $productFactory->create();
$product3->setAttributeSetId(4)
    ->setName('Simple Product - Bundle Option 3')
    ->setSku('simple_option_3')
    ->setPrice(30)
    ->setWebsiteIds([$defaultWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product3);

$product4 = $productFactory->create();
$product4->setAttributeSetId(4)
    ->setName('Simple Product - Bundle Option 4')
    ->setSku('simple_option_4')
    ->setPrice(40)
    ->setWebsiteIds([$defaultWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product4);

$product5 = $productFactory->create();
$product5->setAttributeSetId(4)
    ->setName('Simple Product - Bundle Option 5')
    ->setSku('simple_option_5')
    ->setPrice(50)
    ->setWebsiteIds([$defaultWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product5);
