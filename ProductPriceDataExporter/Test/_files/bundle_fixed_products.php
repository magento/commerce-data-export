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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionFactory;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Bundle\Api\Data\LinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Store\Model\Store;

/**
 * @param string $sku
 * @param array $websiteIds
 * @return ProductInterface
 * @throws \Magento\Framework\Exception\CouldNotSaveException
 * @throws \Magento\Framework\Exception\InputException
 * @throws \Magento\Framework\Exception\NoSuchEntityException
 * @throws \Magento\Framework\Exception\StateException
 */
$createBundleProduct = static function (
    string $sku,
    array $websiteIds
): ProductInterface {
    $objectManager = Bootstrap::getObjectManager();

    /** @var ProductInterfaceFactory $productFactory */
    $productFactory = $objectManager->get(ProductInterfaceFactory::class);
    /** @var ProductExtensionFactory $extensionAttributesFactory */
    $extensionAttributesFactory = $objectManager->get(ProductExtensionFactory::class);
    /** @var OptionInterfaceFactory $optionFactory */
    $optionFactory = $objectManager->get(OptionInterfaceFactory::class);
    /** @var LinkInterfaceFactory $linkFactory */
    $linkFactory = $objectManager->get(LinkInterfaceFactory::class);
    /** @var ProductRepositoryInterface $productRepository */
    $productRepository = $objectManager->create(ProductRepositoryInterface::class);

    $product = $productRepository->get('simple_option_1');
    $product2 = $productRepository->get('simple_option_2');
    $product3 = $productRepository->get('simple_option_3');

    $bundleProduct = $productFactory->create();
    $bundleProduct->setTypeId(Type::TYPE_BUNDLE)
        ->setAttributeSetId($bundleProduct->getDefaultAttributeSetId())
        ->setWebsiteIds($websiteIds)
        ->setName('Bundle Product')
        ->setSku($sku)
        ->setPrice(100.10)
        ->setUrlKey('url_key_' . $sku)
        ->setVisibility(Visibility::VISIBILITY_BOTH)
        ->setStatus(Status::STATUS_ENABLED)
        ->setStockData(
            [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ]
        )
        ->setSkuType(0)
        ->setPriceView(0)
        ->setPriceType(Price::PRICE_TYPE_FIXED)
        ->setWeightType(0)
        ->setShipmentType(AbstractType::SHIPMENT_TOGETHER)
        ->setBundleOptionsData(
            [
                [
                    'title' => 'Option 1',
                    'default_title' => 'Option 1',
                    'type' => 'multi',
                    'required' => 1,
                ],
            ]
        )->setBundleSelectionsData(
            [
                [
                    [
                        'sku' => $product->getSku(),
                        'selection_qty' => 1,
                        'selection_price_value' => 10,
                        'selection_price_type' => 0,
                        'selection_can_change_qty' => 1,
                        'delete' => '',
                    ],
                    [
                        'sku' => $product2->getSku(),
                        'selection_qty' => 1,
                        'selection_price_value' => 25,
                        'selection_price_type' => 1,
                        'selection_can_change_qty' => 1,
                        'delete' => '',
                    ],
                    [
                        'sku' => $product3->getSku(),
                        'selection_qty' => 1,
                        'selection_price_value' => 50,
                        'selection_price_type' => 1,
                        'selection_can_change_qty' => 1,
                        'delete' => '',
                    ],
                ]
            ]
        );

    $options = [];
    foreach ($bundleProduct->getBundleOptionsData() as $key => $optionData) {
        $option = $optionFactory->create(['data' => $optionData]);
        $option->setSku($bundleProduct->getSku());
        $option->setOptionId(null);
        $links = [];
        foreach ($bundleProduct->getBundleSelectionsData()[$key] as $linkData) {
            $link = $linkFactory->create(['data' => $linkData]);
            $link->setSku($linkData['sku']);
            $link->setQty($linkData['selection_qty']);
            $links[] = $link;
        }
        $option->setProductLinks($links);
        $options[] = $option;
    }
    $extensionAttributes = $bundleProduct->getExtensionAttributes() ?: $extensionAttributesFactory->create();
    $extensionAttributes->setBundleProductOptions($options);
    $bundleProduct->setExtensionAttributes($extensionAttributes);

    return $productRepository->save($bundleProduct);
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
Resolver::getInstance()->requireDataFixture(
    'Magento_ProductPriceDataExporter::Test/_files/bundle_option_products.php'
);

/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$firstWebsiteId = $websiteRepository->get('base')->getId();
$secondWebsiteId = $websiteRepository->get('test')->getId();

$store = Bootstrap::getObjectManager()->create(Store::class);
$firstWebsiteStoreId = $store->load('default', 'code')->getStoreId();
$secondWebsiteStoreId = $store->load('fixture_second_store', 'code')->getStoreId();

$productRepository->cleanCache();

$bundleFixedPriceProduct = $createBundleProduct(
    'bundle_fixed_product_with_regular_price',
    [$firstWebsiteId, $secondWebsiteId]
);
// Update product regular prices
$productRepository->save(
    $productRepository->get('bundle_fixed_product_with_regular_price', true, $firstWebsiteStoreId)
        ->setPrice(100.10)
);
$productRepository->save(
    $productRepository->get('bundle_fixed_product_with_regular_price', true, $secondWebsiteStoreId)
        ->setPrice(105.10)
);

$bundleFixedSpecialPrice = $createBundleProduct(
    'bundle_fixed_product_with_special_price',
    [$firstWebsiteId, $secondWebsiteId]
);
// Update product special prices
$productRepository->save(
    $productRepository->get('bundle_fixed_product_with_special_price', true, $firstWebsiteStoreId)
        ->setPrice(150.15)
        ->setData('special_price', 50.50)
);
$productRepository->save(
    $productRepository->get('bundle_fixed_product_with_special_price', true, $secondWebsiteStoreId)
        ->setPrice(155.15)
        ->setData('special_price', 55.55)
);

$bundleFixedTierPrice = $createBundleProduct(
    'bundle_fixed_product_with_tier_price',
    [$firstWebsiteId, $secondWebsiteId]
);

// Create TierPrice
/** First website tier prices */
$tierPriceExtensionAttributesFirstWsAll = $tierPriceExtensionAttributesFactory->create()
    ->setWebsiteId($firstWebsiteId)
    ->setPercentageValue(16.16);
$tierPriceExtensionAttributesFirstWsNotLogged = $tierPriceExtensionAttributesFactory->create()
    ->setWebsiteId($firstWebsiteId)
    ->setPercentageValue(15.15);
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => \Magento\Customer\Model\Group::CUST_GROUP_ALL,
        'qty'=> 1
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesFirstWsAll);
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
        'qty'=> 1
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesFirstWsNotLogged);

/** Second website tier prices */
$tierPriceExtensionAttributesSecondWsAll = $tierPriceExtensionAttributesFactory->create()
    ->setWebsiteId($secondWebsiteId)
    ->setPercentageValue(14.14);
$tierPriceExtensionAttributesSecondWsNotLogged = $tierPriceExtensionAttributesFactory->create()
    ->setWebsiteId($secondWebsiteId)
    ->setPercentageValue(13.13);
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => \Magento\Customer\Model\Group::CUST_GROUP_ALL,
        'qty'=> 1
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesSecondWsAll);
$productTierPrices[] = $tierPriceFactory->create([
    'data' => [
        'customer_group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
        'qty'=> 1
    ]
])->setExtensionAttributes($tierPriceExtensionAttributesSecondWsNotLogged);
$bundleFixedTierPrice->setTierPrices($productTierPrices);
$productRepository->save($bundleFixedTierPrice);
