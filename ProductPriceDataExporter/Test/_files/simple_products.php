<?php
/**
 * Copyright 2024 Adobe
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
use Magento\Customer\Model\Group;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Store;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductInterfaceFactory $productFactory */
$productFactory = $objectManager->get(ProductInterfaceFactory::class);

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var ProductTierPriceInterfaceFactory $tierPriceFactory */
$tierPriceFactory = $objectManager->get(ProductTierPriceInterfaceFactory::class);

/** @var ProductTierPriceExtensionFactory $tierPriceExtensionAttributesFactory */
$tierPriceExtensionAttributesFactory = $objectManager->get(ProductTierPriceExtensionFactory::class);

Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/second_website_with_store_and_store_view.php'
);

/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$firstWebsiteId = $websiteRepository->get('base')->getId();
$secondWebsiteId = $websiteRepository->get('test')->getId();

$store = Bootstrap::getObjectManager()->create(Store::class);
$firstWebsiteStoreId = $store->load('default', 'code')->getStoreId();
$secondWebsiteStoreId = $store->load('fixture_second_store', 'code')->getStoreId();

$productRepository->cleanCache();

// Create simple product with regular price
$product1 = $productFactory->create();
$product1->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product With Regular Price')
    ->setSku('simple_product_with_regular_price')
    ->setPrice(100)
    ->setWebsiteIds([$firstWebsiteId, $secondWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product1);

// Set different prices for multiple websites
$productRepository->save(
    $productRepository->get('simple_product_with_regular_price', true, $firstWebsiteStoreId)->setPrice(50.50)
);
$productRepository->save(
    $productRepository->get('simple_product_with_regular_price', true, $secondWebsiteStoreId)->setPrice(55.55)
);

// Create simple product with special price
$product2 = $productFactory->create();
$product2->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product With Special Price')
    ->setSku('simple_product_with_special_price')
    ->setPrice(100.10)
    ->setData('special_price', 45.00)
    ->setWebsiteIds([$firstWebsiteId, $secondWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product2);

// Set different special prices for multiple websites
$productRepository->save(
    $productRepository->get('simple_product_with_special_price', true, $firstWebsiteStoreId)
        ->setData('special_price', 50.50)
);
$productRepository->save(
    $productRepository->get('simple_product_with_special_price', true, $secondWebsiteStoreId)
        ->setData('special_price', 55.55)
);
// Create virtual product with special price
$productVirtual = $productFactory->create();
$productVirtual->setTypeId(Type::TYPE_VIRTUAL)
    ->setAttributeSetId(4)
    ->setName('Virtual Product With Special Price')
    ->setSku('virtual_product_with_special_price')
    ->setPrice(100.10)
    ->setData('special_price', 45.00)
    ->setWebsiteIds([$firstWebsiteId, $secondWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($productVirtual);
// Set different special prices for multiple websites
$productRepository->save(
    $productRepository->get('virtual_product_with_special_price', true, $firstWebsiteStoreId)
        ->setData('special_price', 50.50)
);
$productRepository->save(
    $productRepository->get('virtual_product_with_special_price', true, $secondWebsiteStoreId)
        ->setData('special_price', 55.55)
);

// Create TierPrice
$tierPriceExtensionAttributesFirstWs = $tierPriceExtensionAttributesFactory->create()->setWebsiteId($firstWebsiteId);
$tierPriceExtensionAttributesFirstWsFirstGroup = $tierPriceExtensionAttributesFactory->create()
    ->setWebsiteId($firstWebsiteId);
$tierPriceExtensionAttributesFirstWsFirstGroup->setPercentageValue(10);
$tierPriceExtensionAttributesSecondWs = $tierPriceExtensionAttributesFactory->create()->setWebsiteId($secondWebsiteId);

/** First website tier prices */
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => Group::CUST_GROUP_ALL,
        'qty'=> 1,
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesFirstWsFirstGroup);

$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => Group::NOT_LOGGED_IN_ID,
        'qty'=> 1,
        'value'=> 15.15
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesFirstWs);

/** Second website tier prices */
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => Group::CUST_GROUP_ALL,
        'percentage_value'=> null,
        'qty'=> 1,
        'value'=> 14.14
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesSecondWs);
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => Group::NOT_LOGGED_IN_ID,
        'percentage_value'=> null,
        'qty'=> 1,
        'value'=> 13.13
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesSecondWs);


// Create simple product with tier price
$productWithTierPrices = $productFactory->create();
$productWithTierPrices->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product With Tier Price')
    ->setSku('simple_product_with_tier_price')
    ->setPrice(100.10)
    ->setWebsiteIds([$firstWebsiteId, $secondWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productWithTierPrices->setTierPrices($productTierPrices);
$productRepository->save($productWithTierPrices);
