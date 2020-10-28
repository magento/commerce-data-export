<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/multiselect_attribute.php');
$objectManager = Bootstrap::getObjectManager();
$eavConfig = $objectManager->get(Config::class);

$multiselectAttribute = $eavConfig->getAttribute(Product::ENTITY, 'multiselect_attribute');


$multiselectOptionsIds = $objectManager->create(Collection::class)
    ->setAttributeFilter($multiselectAttribute->getId())
    ->getAllIds();

/** @var \Magento\Catalog\Model\ProductFactory $productFactory */
$productFactory = $objectManager->get(Magento\Catalog\Model\ProductFactory::class);
$product = $productFactory->create();
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setAttributeSetId($product->getDefaultAttributeSetId())
    ->setWebsiteIds([1])
    ->setName('Simple Product with Multiselect')
    ->setSku('simple_with_multiselect')
    ->setMultiselectAttribute($multiselectOptionsIds[0])
    ->setPrice(10)
    ->setDescription('Description with <b>html tag</b>')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setCategoryIds([2])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setUrlKey('simple_with_multiselect')
    ->save();
