<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionFactory;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductInterfaceFactory $productFactory */
$productFactory = $objectManager->get(ProductInterfaceFactory::class);

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var ProductTierPriceInterfaceFactory $tierPriceFactory */
$tierPriceFactory = $objectManager->get(ProductTierPriceInterfaceFactory::class);

/** @var ProductTierPriceExtensionFactory $tierPriceExtensionAttributesFactory */
$tierPriceExtensionAttributesFactory = $objectManager->get(ProductTierPriceExtensionFactory::class);

$productRepository->cleanCache();

// Create simple product with regular price
$product1 = $productFactory->create();
$product1->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product With Regular Price')
    ->setSku('simple_product_with_regular_price')
    ->setPrice(10)
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product1);

// Create simple product with special price
$product2 = $productFactory->create();
$product2->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product With Special Price')
    ->setSku('simple_product_with_special_price')
    ->setPrice(20)
    ->setData('special_price', 5)
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product2);

// Create virtual product with special price
$productVirtual = $productFactory->create();
$productVirtual->setTypeId(Type::TYPE_VIRTUAL)
    ->setAttributeSetId(4)
    ->setName('Virtual Product With Special Price')
    ->setSku('virtual_product_with_special_price')
    ->setPrice(200)
    ->setData('special_price', 50)
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($productVirtual);

// Create Grouped product
$productGrouped = $productFactory->create();
$productGrouped->setTypeId(Grouped::TYPE_CODE)
    ->setAttributeSetId(4)
    ->setName('Grouped Product')
    ->setSku('grouped_product')
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($productGrouped);

// Create Bundle product
$productBundle = $productFactory->create();
$productBundle->setTypeId(Type::TYPE_BUNDLE)
    ->setAttributeSetId(4)
    ->setName('Bundle Product')
    ->setSku('bundle_product')
    ->setPriceView(0)
    ->setPriceType(Price::PRICE_TYPE_DYNAMIC)
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($productBundle);

// Create simple product with special price for CG
$productSimpleWithCg = $productFactory->create();
$productSimpleWithCg->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product With Special Price for CG')
    ->setSku('simple_product_with_special_price_for_cg')
    ->setPrice(30)
    ->setStatus(Status::STATUS_ENABLED);

// Create TierPrice
$tierPriceExtensionAttributes = $tierPriceExtensionAttributesFactory->create()->setWebsiteId(1);
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => '1',
        'percentage_value'=> null,
        'qty'=> 1,
        'value'=> 15
    ]
])->setExtensionAttributes($tierPriceExtensionAttributes);

$productSimpleWithCg->setTierPrices($productTierPrices);
$productRepository->save($productSimpleWithCg);