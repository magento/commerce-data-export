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

Resolver::getInstance()->requireDataFixture('Magento/ConfigurableProduct/_files/configurable_product_two_websites.php');

$store = Bootstrap::getObjectManager()->create(Store::class);
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$firstWebsiteId = $websiteRepository->get('base')->getId();
$secondWebsiteId = $websiteRepository->get('test')->getId();
$firstWebsiteStoreId = $store->load('default', 'code')->getStoreId();
$secondWebsiteStoreId = $store->load('fixture_second_store', 'code')->getStoreId();

// Workaround to add store group to second website
$secondWebsite = $websiteRepository->get('test');
$rootCategoryId = $websiteRepository->get('base')->getDefaultStore()->getRootCategoryId();
$storeGroup = $objectManager->create(\Magento\Store\Model\Group::class);
$storeGroup->setCode('second_store_group')
    ->setName('Second Store Group')
    ->setRootCategoryId($rootCategoryId)
    ->setWebsite($secondWebsite);
try {
    $storeGroup->save();
} catch (Exception $e) {
}

$store->load('fixture_second_store', 'code');
$groupId = $storeGroup->getId();
$store->setGroupId($groupId);
$store->save();

$productRepository->cleanCache();

// Update product prices
$productRepository->save(
    $productRepository->get('simple_option_1', true, $firstWebsiteStoreId)
        ->setPrice(150.15)
        ->setData('special_price', 10.10)
);
$productRepository->save(
    $productRepository->get('simple_option_1', true, $secondWebsiteStoreId)
        ->setPrice(155.15)
        ->setData('special_price', 15.15)
);

// Create TierPrice
$tierPriceExtensionAttributesFirstWs = $tierPriceExtensionAttributesFactory->create()->setWebsiteId($firstWebsiteId);
$tierPriceExtensionAttributesSecondWs = $tierPriceExtensionAttributesFactory->create()->setWebsiteId($secondWebsiteId);

/** First website tier prices */
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => \Magento\Customer\Model\Group::CUST_GROUP_ALL,
        'percentage_value'=> null,
        'qty'=> 1,
        'value'=> 16.16
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesFirstWs);
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
        'percentage_value'=> null,
        'qty'=> 1,
        'value'=> 15.15
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesFirstWs);

/** Second website tier prices */
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => \Magento\Customer\Model\Group::CUST_GROUP_ALL,
        'percentage_value'=> null,
        'qty'=> 1,
        'value'=> 14.14
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesSecondWs);
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
        'percentage_value'=> null,
        'qty'=> 1,
        'value'=> 13.13
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesSecondWs);

$product2 = $productRepository->get('simple_option_2');
$product2->setTierPrices($productTierPrices);
$productRepository->save($product2);
