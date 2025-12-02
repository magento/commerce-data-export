<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionFactory;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Customer\Model\Group;
use Magento\Framework\EntityManager\EntityMetadataInterface;
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
$createGroupedPrice($productTierPrices, $firstWebsiteId, Group::CUST_GROUP_ALL, 2, 20, null);
$createGroupedPrice($productTierPrices, $firstWebsiteId, Group::NOT_LOGGED_IN_ID, 3, null, 15.15);
$createGroupedPrice($productTierPrices, $firstWebsiteId, Group::NOT_LOGGED_IN_ID, 4, null, 14.15);

/** Second website tier prices */
$createGroupedPrice($productTierPrices, $secondWebsiteId, Group::CUST_GROUP_ALL, 2, null, 14.14);
$createGroupedPrice($productTierPrices, $secondWebsiteId, Group::NOT_LOGGED_IN_ID, 3, null, 13.13);
$createGroupedPrice($productTierPrices, $secondWebsiteId, Group::NOT_LOGGED_IN_ID, 4, null, 12.13);


// Create simple product with tier price
$productWithTierPrices = $productFactory->create();
$productWithTierPrices->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product With Tier Prices')
    ->setSku('simple_product_with_tier_prices')
    ->setPrice(100.10)
    ->setWebsiteIds([$firstWebsiteId, $secondWebsiteId])
    ->setStatus(Status::STATUS_ENABLED);
$productWithTierPrices->setTierPrices($productTierPrices);
$product = $productRepository->save($productWithTierPrices);

// Emulate behavior where a tier price record has both 'value' and 'percentage_value' set
// This updates the tier price for qty=2, customer_group_id=ALL_GROUPS, first website
/** @var \Magento\Framework\App\ResourceConnection $resourceConnection */
$resourceConnection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resourceConnection->getConnection();
$tierPriceTable = $resourceConnection->getTableName('catalog_product_entity_tier_price');

/** @var EntityMetadataInterface $metadata */
$metadata = $objectManager->get(\Magento\Framework\EntityManager\MetadataPool::class)
    ->getMetadata(ProductInterface::class);
$linkField = $metadata->getLinkField();

$connection->update(
    $tierPriceTable,
    ['value' => 20],
    [
        "$linkField = ?" => $product->getId(),
        'all_groups = ?' => 1,
        'customer_group_id = ?' => 0,
        'qty = ?' => 2.0,
        'website_id = ?' => $firstWebsiteId
    ]
);
