<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
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

$createGroupedPrice = static function (
    &$productTierPrices,
    $websiteId,
    $customerGroupId,
    $qty,
    $percentageValue = null,
    $priceValue = null
) {
    $objectManager = Bootstrap::getObjectManager();
    /** @var ProductTierPriceExtensionFactory $tierPriceExtensionAttributesFactory */
    $tierPriceExtensionAttributesFactory = $objectManager->get(ProductTierPriceExtensionFactory::class);
    /** @var ProductTierPriceInterfaceFactory $tierPriceFactory */
    $tierPriceFactory = $objectManager->get(ProductTierPriceInterfaceFactory::class);

    $tierPriceExtensionAttributes = $tierPriceExtensionAttributesFactory->create()
        ->setWebsiteId($websiteId);
    $data = [];
    if ($percentageValue !== null) {
        $tierPriceExtensionAttributes->setPercentageValue($percentageValue);
        $data = [
            'customer_group_id' => $customerGroupId,
            'qty'=> $qty
        ];
    } elseif ($priceValue !== null) {
        $data = [
                'customer_group_id' => $customerGroupId,
                'percentage_value'=> null,
                'qty'=> $qty,
                'value'=> $priceValue
            ];
    }
    $productTierPrices[] = $tierPriceFactory->create(['data' => $data])
        ->setExtensionAttributes($tierPriceExtensionAttributes);
};

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

// Create tier prices
$productTierPrices = [];
/** First website tier prices */
$createGroupedPrice($productTierPrices, $firstWebsiteId, Group::CUST_GROUP_ALL, 1, 10, null);
$createGroupedPrice($productTierPrices, $firstWebsiteId, Group::CUST_GROUP_ALL, 2, 20, null);
$createGroupedPrice($productTierPrices, $firstWebsiteId, Group::NOT_LOGGED_IN_ID, 3, null, 15.15);
$createGroupedPrice($productTierPrices, $firstWebsiteId, Group::NOT_LOGGED_IN_ID, 4, null, 14.15);

/** Second website tier prices */
$createGroupedPrice($productTierPrices, $secondWebsiteId, Group::CUST_GROUP_ALL, 1, null, 15.14);
$createGroupedPrice($productTierPrices, $secondWebsiteId, Group::CUST_GROUP_ALL, 2, null, 14.14);
$createGroupedPrice($productTierPrices, $secondWebsiteId, Group::NOT_LOGGED_IN_ID, 3, null, 13.13);
$createGroupedPrice($productTierPrices, $secondWebsiteId, Group::NOT_LOGGED_IN_ID, 4, null, 12.13);


// Create simple product with tier price
$productWithTierPrices = $productFactory->create();
$productWithTierPrices->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product With Tier And Grouped Prices')
    ->setSku('simple_product_with_tier_and_grouped_prices')
    ->setPrice(100.10)
    ->setWebsiteIds([$firstWebsiteId, $secondWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productWithTierPrices->setTierPrices($productTierPrices);
$productRepository->save($productWithTierPrices);
