<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionFactory;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Store\Api\WebsiteRepositoryInterface;

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
$firstWebsite = $websiteRepository->get('base');
$secondWebsite = $websiteRepository->get('test');
$firstWebsiteStoreId = $store->load('default', 'code')->getStoreId();
$secondWebsiteStoreId = $store->load('fixture_second_store', 'code')->getStoreId();

// Workaround to add store group to second website
$storeGroup = $objectManager->create(\Magento\Store\Model\Group::class);
$storeGroup->setCode('second_store_group')
    ->setName('Second Store Group')
    ->setRootCategoryId($firstWebsite->getDefaultStore()->getRootCategoryId())
    ->setWebsite($secondWebsite);
try {
    $storeGroup->save();
} catch (Exception) {
}

$store->load('fixture_second_store', 'code');

$store->setGroupId($storeGroup->getId());
$store->save();

$productRepository->cleanCache();

$productRepository->save(
    $productRepository->get('simple_option_1', true, $firstWebsiteStoreId)->setPrice(50.50)
);
$productRepository->save(
    $productRepository->get('simple_option_1', true, $secondWebsiteStoreId)->setPrice(55.55)
);
$productRepository->save(
    $productRepository->get('simple_option_2', true, $firstWebsiteStoreId)->setPrice(100.10)
);
$productRepository->save(
    $productRepository->get('simple_option_2', true, $secondWebsiteStoreId)->setPrice(105.10)
);
