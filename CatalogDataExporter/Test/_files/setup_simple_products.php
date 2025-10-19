<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_stores.php');
Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_categories.php');
Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_attributes.php');
Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_catalog_rule.php');

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Magento\Catalog\Api\CategoryLinkManagementInterface $linkManagement */
$categoryLinkManagement = $objectManager->create(CategoryLinkManagementInterface::class);


/** @var Set $attributeSet */
$attributeSet = $objectManager->create(Set::class);
$attributeSet->load('SaaSCatalogAttributeSet', 'attribute_set_name');

/** @var \Magento\Eav\Model\AttributeRepository $attributeRepository */
$attributeRepository = $objectManager->create(\Magento\Eav\Model\AttributeRepository::class);
$attribute = $attributeRepository->get('catalog_product', 'custom_select');
/** @var Collection $options */
$options = $objectManager->create(Collection::class);
$options->setAttributeFilter($attribute->getId());
$optionIds = $options->getAllIds();

/** @var $product Product */
$product = $objectManager->create(Product::class);
$product->isObjectNew(true);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setId(10)
    ->setAttributeSetId($attributeSet->getId())
    ->setName('Simple Product1')
    ->setSku('simple1')
    ->setTaxClassId(2) // Taxable Goods
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
    ->setWebsiteIds([1])
    ->setStockData([
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
            'backorders' => 1,
            'use_config_min_sale_qty' => 0,
            'min_sale_qty' => 3,
            'max_sale_qty' => 100,
            'use_config_enable_qty_inc' => 0,
            'enable_qty_inc' => 1,
            'qty_increments' => 2
        ]
    )->setSpecialPrice('50.99')
    ->setImage('/m/a/magento_image.jpg')
    ->setSmallImage('/m/a/magento_small_image.jpg')
    ->setThumbnail('/m/a/magento_thumbnail.jpg')
    ->setNewsFromDate(date('Y-m-d H:i:s', strtotime('-2 day')))
    ->setNewsToDate(date('Y-m-d H:i:s', strtotime('+2 day')))
    ->setCustomAttribute('custom_label', 'comma, separated, values')
    ->setCustomAttribute('custom_description', 'description1')
    ->setCustomSelect($optionIds[0])
    ->setCustomAttribute('yes_no_attribute', 1)
    ->save();
$categoryLinkManagement->assignProductToCategories($product->getSku(), [100, 200]);

$product = $objectManager->create(Product::class);
$product->isObjectNew(true);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setId(11)
    ->setAttributeSetId($attributeSet->getId())
    ->setName('Simple Product2')
    ->setSku('simple2')
    ->setTaxClassId(2) // Taxable Goods
    ->setDescription('description')
    ->setShortDescription('short description')
    ->setOptionsContainer('container1')
    ->setMsrpDisplayActualPriceType(\Magento\Msrp\Model\Product\Attribute\Source\Type::TYPE_ON_GESTURE)
    ->setPrice(100)
    ->setWeight(1)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_IN_CATALOG)
    ->setStatus(Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 50, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setSpecialPrice('95.99')
    ->setImage('/m/a/magento_image.jpg')
    ->setSmallImage('/m/a/magento_small_image.jpg')
    ->setThumbnail('/m/a/magento_thumbnail.jpg')
    ->setCustomAttribute('custom_label', 'label1')
    ->setCustomAttribute('custom_description', 'description, <b>data</b>')
    ->setCustomSelect($optionIds[1])
    ->setCustomAttribute('yes_no_attribute', 0)
    ->save();
$categoryLinkManagement->assignProductToCategories($product->getSku(), [100, 200]);

$product = $objectManager->create(Product::class);
$product->isObjectNew(true);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setId(12)
    ->setAttributeSetId($attributeSet->getId())
    ->setName('Simple Product3')
    ->setSku('simple3')
    ->setTaxClassId(2) // Taxable Goods
    ->setDescription('description')
    ->setShortDescription('short description')
    ->setPrice(30)
    ->setWeight(1)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_IN_CATALOG)
    ->setStatus(Status::STATUS_DISABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 140, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setSpecialPrice('25.99')
    ->setImage('/m/a/magento_image.jpg')
    ->setSmallImage('/m/a/magento_small_image.jpg')
    ->setThumbnail('/m/a/magento_thumbnail.jpg')
    ->setCustomAttribute('custom_label', 'label1')
    ->setCustomAttribute('custom_description', 'description1')
    ->save();
$categoryLinkManagement->assignProductToCategories($product->getSku(), [100, 200]);
