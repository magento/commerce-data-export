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
Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/second_website_with_store_and_store_view.php'
);
Resolver::getInstance()->requireDataFixture('Magento/GroupedProduct/_files/product_grouped_in_multiple_websites.php');

$store = Bootstrap::getObjectManager()->create(Store::class);
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$firstWebsite = $websiteRepository->get('base');
$secondWebsite = $websiteRepository->get('test');
$firstWebsiteStoreId = $store->load('default', 'code')->getStoreId();
$secondWebsiteStoreId = $store->load('fixture_second_store', 'code')->getStoreId();

// Assign grouped product to multiple websites
$productRepository->save(
    $productRepository->get('grouped-product')->setWebsiteIds([$firstWebsite->getId(), $secondWebsite->getId()])
);

// Assign websites and prices to child products
$productRepository->save(
    $productRepository->get('simple', true, $firstWebsiteStoreId)->setPrice(50.50)
);
$productRepository->save(
    $productRepository->get('simple', true, $secondWebsiteStoreId)->setPrice(55.55)
);
$productRepository->save(
    $productRepository->get('virtual-product', true, $firstWebsiteStoreId)->setPrice(150.15)
);
$productRepository->save(
    $productRepository->get('virtual-product', true, $secondWebsiteStoreId)->setPrice(155.15)
);
