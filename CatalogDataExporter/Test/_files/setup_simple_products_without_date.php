<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

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
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setSpecialPrice('50.99')
    ->setImage('/m/a/magento_image.jpg')
    ->setSmallImage('/m/a/magento_small_image.jpg')
    ->setThumbnail('/m/a/magento_thumbnail.jpg')
    ->setCustomAttribute('custom_label', 'comma, separated, values')
    ->setCustomAttribute('custom_description', 'description1')
    ->setCustomSelect($optionIds[0])
    ->setCustomAttribute('yes_no_attribute', 1)
    ->setCreatedAt('0000-00-00 00:00:00')
    ->setUpdatedAt('')
    ->save();
$categoryLinkManagement->assignProductToCategories($product->getSku(), [100, 200]);
