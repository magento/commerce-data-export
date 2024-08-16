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

use Magento\Eav\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/setup_two_stores_and_two_websites.php'
);

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

$defaultAttributeSet = $objectManager->get(Config::class)->getEntityType(Product::ENTITY)->getDefaultAttributeSetId();

/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$secondWebsite = $websiteRepository->get('test');

/** @var $product Product */
$product = $objectManager->create(Product::class);
$product->isObjectNew(true);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setId(10)
    ->setName('Simple Product1')
    ->setSku('simple1')
    ->setAttributeSetId($defaultAttributeSet)
    ->setTaxClassId('none')
    ->setDescription('description')
    ->setShortDescription('short description')
    ->setOptionsContainer('container1')
    ->setMsrpDisplayActualPriceType(\Magento\Msrp\Model\Product\Attribute\Source\Type::TYPE_IN_CART)
    ->setPrice(100)
    ->setWeight(1)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setWebsiteIds([1, $secondWebsite->getId()])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setSpecialPrice('50.99')
    ->setImage('/m/a/magento_image.jpg')
    ->setSmallImage('/m/a/magento_small_image.jpg')
    ->setThumbnail('/m/a/magento_thumbnail.jpg')
    ->setCustomAttribute('custom_label', 'label1')
    ->setCustomAttribute('custom_description', 'description1');

$oldOptions = [
    [
        'previous_group' => 'select',
        'title' => 'Test Select',
        'type' => 'drop_down',
        'is_require' => 1,
        'sort_order' => 0,
        'values' => [
            [
                'option_type_id' => null,
                'title' => 'Option 1',
                'price' => '-3,000.00',
                'price_type' => 'fixed',
                'sku' => '3-1-select',
            ],
            [
                'option_type_id' => null,
                'title' => 'Option 2',
                'price' => '5,000.00',
                'price_type' => 'fixed',
                'sku' => '3-2-select',
            ],
        ]
    ],
    [
        'previous_group' => 'select',
        'title' => 'Test Radio',
        'type' => 'radio',
        'is_require' => 1,
        'sort_order' => 0,
        'values' => [
            [
                'option_type_id' => null,
                'title' => 'Option 1',
                'price' => '600.234',
                'price_type' => 'fixed',
                'sku' => '4-1-radio',
            ],
            [
                'option_type_id' => null,
                'title' => 'Option 2',
                'price' => '40,000.00',
                'price_type' => 'fixed',
                'sku' => '4-2-radio',
            ],
        ]
    ],
    [
        'previous_group' => 'text',
        'title'     => 'Test Field',
        'type'      => 'field',
        'is_require' => 1,
        'sort_order' => 0,
        'price'     => 1,
        'price_type' => 'fixed',
        'sku'       => '1-text',
        'max_characters' => 100,
    ],
];

$options = [];

/** @var \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory $customOptionFactory */
$customOptionFactory = $objectManager->create(\Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory::class);

foreach ($oldOptions as $option) {
    /** @var \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option */
    $option = $customOptionFactory->create(['data' => $option]);
    $option->setProductSku($product->getSku());

    $options[] = $option;
}

$product->setOptions($options);
/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryFactory */
$productRepositoryFactory = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$productRepositoryFactory->save($product);
