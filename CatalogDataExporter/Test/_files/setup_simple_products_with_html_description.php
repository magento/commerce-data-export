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
    ->setName('Simple Product With HTML Description')
    ->setSku('simple_html_description')
    ->setTaxClassId('none')
    ->setDescription('<style>#html-body [data-pb-style=KGJ9YC7]{background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;align-self:stretch}#html-body [data-pb-style=SQ83KIC]{display:flex;width:100%}#html-body [data-pb-style=C71MH3Q],#html-body [data-pb-style=SYXWUC5]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;width:50%;align-self:stretch}</style><div data-content-type="block" data-appearance="default" data-element="main">{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="2" type_name="CMS Static Block"}}</div><div class="pagebuilder-column-group" data-background-images="{}" data-content-type="column-group" data-appearance="default" data-grid-size="12" data-element="main" data-pb-style="KGJ9YC7"><div class="pagebuilder-column-line" data-content-type="column-line" data-element="main" data-pb-style="SQ83KIC"><div class="pagebuilder-column" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="C71MH3Q"><div data-content-type="text" data-appearance="default" data-element="main"><p>Test 2</p></div></div><div class="pagebuilder-column" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="SYXWUC5"><h2 data-content-type="heading" data-appearance="default" data-element="main">Test 3</h2></div></div></div>')
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
    ->save();
$categoryLinkManagement->assignProductToCategories($product->getSku(), [100, 200]);
