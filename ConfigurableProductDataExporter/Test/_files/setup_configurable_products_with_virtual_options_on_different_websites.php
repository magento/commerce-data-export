<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Setup\CategorySetup;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Msrp\Model\Product\Attribute\Source\Type;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Store\Api\WebsiteRepositoryInterface;

Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/setup_two_stores_and_two_websites.php'
);
Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/setup_categories.php'
);
Resolver::getInstance()->requireDataFixture(
    'Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_attribute.php'
);
/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

$firstWebsiteProducts = [50, 60, 70];
$secondWebsiteProducts = [55, 59, 65];

$attributesMethodName = [
    'first_test_configurable' => 'setFirstTestConfigurable',
    'second_test_configurable' => 'setSecondTestConfigurable'
];

/** @var Magento\Catalog\Api\CategoryLinkManagementInterface $linkManagement */
$categoryLinkManagement = $objectManager->create(CategoryLinkManagementInterface::class);

/** @var CategorySetup $installer */
$installer = $objectManager->create(CategorySetup::class);

/** @var \Magento\Eav\Model\AttributeRepository $attributeRepository */
$attributeRepository = $objectManager->create(\Magento\Eav\Model\AttributeRepository::class);

$attributeSetId = $installer->getAttributeSetId('catalog_product', 'Default');

$associatedProductIds = [];
$configurableAttributesData = [];
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$website2 = $websiteRepository->get('test');
$attribute = $attributeRepository->get('catalog_product', 'first_test_configurable');

$attributeValues = [];

/* Create simple products per each option value*/
/** @var AttributeOptionInterface[] $options */
$options = $attribute->getOptions();
array_shift($options); //remove the first option which is empty

foreach ($options as $option) {
    if ($option->getLabel() === 'Option 4') {
        continue;
    }
    /** @var Product $product */
    $product = $objectManager->create(Product::class);
    $productId = array_shift($firstWebsiteProducts);
    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($attributeSetId)
        ->setName('Configurable Option' . $option->getLabel())
        ->setSku('virtual_option_' . $productId)
        ->setTaxClassId('none')
        ->setDescription('description')
        ->setShortDescription('short description')
        ->setPrice($productId)
        ->setWeight(1)
        ->setMetaTitle('meta title')
        ->setMetaKeyword('meta keyword')
        ->setMetaDescription('meta description')
        ->setFirstTestConfigurable($option->getValue())
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setWebsiteIds([1, $website2->getId()])
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
        ->setSpecialPrice('5.99')
        ->setImage('/m/a/magento_image.jpg')
        ->setSmallImage('/m/a/magento_small_image.jpg')
        ->setThumbnail('/m/a/magento_thumbnail.jpg')
        ->save();

    $attributeValues[] = [
        'label' => 'test',
        'attribute_id' => $attribute->getId(),
        'value_index' => $option->getValue(),
    ];
    $firstWebsiteAssociatedProductIds[] = $product->getId();
}

$firstWebsiteConfigurableAttributesData[] = [
    'attribute_id' => $attribute->getId(),
    'code' => $attribute->getAttributeCode(),
    'label' => $attribute->getStoreLabel(),
    'position' => '0',
    'values' => $attributeValues
];

$attribute = $attributeRepository->get('catalog_product', 'first_test_configurable');

$attributeValues = [];

/* Create simple products per each option value*/
/** @var AttributeOptionInterface[] $options */
$options = $attribute->getOptions();
array_shift($options); //remove the first option which is empty

foreach ($options as $option) {
    if ($option->getLabel() === 'Option 4') {
        continue;
    }
    /** @var Product $product */
    $product = $objectManager->create(Product::class);
    $productId = array_shift($secondWebsiteProducts);
    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($attributeSetId)
        ->setName('Configurable Option' . $option->getLabel())
        ->setSku('virtual_option_' . $productId)
        ->setTaxClassId('none')
        ->setDescription('description')
        ->setShortDescription('short description')
        ->setPrice($productId)
        ->setWeight(1)
        ->setMetaTitle('meta title')
        ->setMetaKeyword('meta keyword')
        ->setMetaDescription('meta description')
        ->setSecondTestConfigurable($option->getValue())
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setWebsiteIds([1, $website2->getId()])
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
        ->setSpecialPrice('5.99')
        ->setImage('/m/a/magento_image.jpg')
        ->setSmallImage('/m/a/magento_small_image.jpg')
        ->setThumbnail('/m/a/magento_thumbnail.jpg')
        ->save();

    $attributeValues[] = [
        'label' => 'test',
        'attribute_id' => $attribute->getId(),
        'value_index' => $option->getValue(),
    ];
    $secondWebsiteAssociatedProductIds[] = $product->getId();
}

$secondWebsiteConfigurableAttributesData[] = [
    'attribute_id' => $attribute->getId(),
    'code' => $attribute->getAttributeCode(),
    'label' => $attribute->getStoreLabel(),
    'position' => '0',
    'values' => $attributeValues
];

/** @var Product $product */
$product = $objectManager->create(Product::class);
/** @var Factory $optionsFactory */
$optionsFactory = $objectManager->create(Factory::class);
$configurableOptions = $optionsFactory->create($firstWebsiteConfigurableAttributesData);
$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($firstWebsiteAssociatedProductIds);

$product->setExtensionAttributes($extensionConfigurableAttributes);
$product->isObjectNew(true);
$product->setTypeId(Configurable::TYPE_CODE)
    ->setId(40)
    ->setAttributeSetId($attributeSetId)
    ->setName('Configurable Product WS 1')
    ->setSku('configurable_ws_1')
    ->setTaxClassId('none')
    ->setDescription('deascription')
    ->setShortDescription('short description')
    ->setOptionsContainer('container1')
    ->setMsrpDisplayActualPriceType(Type::TYPE_IN_CART)
    ->setPrice(10)
    ->setWeight(1)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setSpecialPrice('5.99')
    ->setImage('/m/a/magento_image.jpg')
    ->setSmallImage('/m/a/magento_small_image.jpg')
    ->setThumbnail('/m/a/magento_thumbnail.jpg')
    ->save();
$categoryLinkManagement->assignProductToCategories($product->getSku(), [100, 200]);

/** @var Product $product */
$product = $objectManager->create(Product::class);
/** @var Factory $optionsFactory */
$configurableOptions = $optionsFactory->create($secondWebsiteConfigurableAttributesData);
$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($secondWebsiteAssociatedProductIds);
$product->setExtensionAttributes($extensionConfigurableAttributes);
$product->isObjectNew(true);
$product->setTypeId(Configurable::TYPE_CODE)
    ->setId(41)
    ->setAttributeSetId($attributeSetId)
    ->setName('Configurable Product WS 2')
    ->setSku('configurable_ws_2')
    ->setTaxClassId('none')
    ->setDescription('description')
    ->setShortDescription('short description')
    ->setOptionsContainer('container1')
    ->setMsrpDisplayActualPriceType(Type::TYPE_IN_CART)
    ->setPrice(10)
    ->setWeight(1)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setWebsiteIds([$website2->getId()])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setSpecialPrice('5.99')
    ->setImage('/m/a/magento_image.jpg')
    ->setSmallImage('/m/a/magento_small_image.jpg')
    ->setThumbnail('/m/a/magento_thumbnail.jpg')
    ->save();
$categoryLinkManagement->assignProductToCategories($product->getSku(), [100, 200]);
