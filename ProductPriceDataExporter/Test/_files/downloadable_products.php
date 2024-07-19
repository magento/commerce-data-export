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
use Magento\Downloadable\Model\Product\Type as Downloadable;
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

// Create Downloadable product with regular price
$productWithRegularPrice = $productFactory->create();
$productWithRegularPrice->setTypeId(Downloadable::TYPE_DOWNLOADABLE)
    ->setAttributeSetId(4)
    ->setName('Downloadable Product With Regular Price')
    ->setSku('downloadable_product_with_regular_price')
    ->setPrice(150.15)
    ->setWebsiteIds([$firstWebsiteId, $secondWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($productWithRegularPrice);
// Set different prices for multiple websites
$productRepository->save(
    $productRepository->get('downloadable_product_with_regular_price', true, $firstWebsiteStoreId)->setPrice(50.50)
);
$productRepository->save(
    $productRepository->get('downloadable_product_with_regular_price', true, $secondWebsiteStoreId)->setPrice(55.55)
);

// Create Downloadable product with special price
$productWithSpecialPrices = $productFactory->create();
$productWithSpecialPrices->setTypeId(Downloadable::TYPE_DOWNLOADABLE)
    ->setAttributeSetId(4)
    ->setName('Downloadable Product With Special Price')
    ->setSku('downloadable_product_with_special_price')
    ->setPrice(150.15)
    ->setWebsiteIds([$firstWebsiteId, $secondWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($productWithSpecialPrices);

// Set different special prices for multiple websites
$productRepository->save(
    $productRepository->get('downloadable_product_with_special_price', true, $firstWebsiteStoreId)
        ->setData('special_price', 50.50)
);
$productRepository->save(
    $productRepository->get('downloadable_product_with_special_price', true, $secondWebsiteStoreId)
        ->setData('special_price', 55.55)
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

// Create Downloadable product with tier price
$productWithTierPrices = $productFactory->create();
$productWithTierPrices->setTypeId(Downloadable::TYPE_DOWNLOADABLE)
    ->setAttributeSetId(4)
    ->setName('Downloadable Product With Tier Price')
    ->setSku('downloadable_product_with_tier_price')
    ->setPrice(150.15)
    ->setWebsiteIds([$firstWebsiteId, $secondWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productWithTierPrices->setTierPrices($productTierPrices);
$productRepository->save($productWithTierPrices);
