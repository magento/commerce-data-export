<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Eav\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_two_stores_and_two_websites.php');

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
    ->setCustomAttribute('custom_description', 'description1')
    ->save();

$product = $objectManager->create(Product::class);
$product->isObjectNew(true);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setId(11)
    ->setName('Simple Product2')
    ->setSku('simple2')
    ->setAttributeSetId($defaultAttributeSet)
    ->setTaxClassId('none')
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
    ->setWebsiteIds([1, $secondWebsite->getId()])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 50, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setSpecialPrice('95.99')
    ->setImage('/m/a/magento_image.jpg')
    ->setSmallImage('/m/a/magento_small_image.jpg')
    ->setThumbnail('/m/a/magento_thumbnail.jpg')
    ->setCustomAttribute('custom_label', 'label1')
    ->setCustomAttribute('custom_description', 'description1')
    ->save();

$product = $objectManager->create(Product::class);
$product->isObjectNew(true);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setId(12)
    ->setName('Simple Product3')
    ->setSku('simple3')
    ->setAttributeSetId($defaultAttributeSet)
    ->setTaxClassId('none')
    ->setDescription('description')
    ->setShortDescription('short description')
    ->setPrice(30)
    ->setWeight(1)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_IN_CATALOG)
    ->setStatus(Status::STATUS_DISABLED)
    ->setWebsiteIds([1, $secondWebsite->getId()])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 140, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setSpecialPrice('25.99')
    ->setImage('/m/a/magento_image.jpg')
    ->setSmallImage('/m/a/magento_small_image.jpg')
    ->setThumbnail('/m/a/magento_thumbnail.jpg')
    ->setCustomAttribute('custom_label', 'label1')
    ->setCustomAttribute('custom_description', 'description1')
    ->save();

