<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var ProductRepositoryInterface $productInterface */
$productInterface = $objectManager->create(ProductRepositoryInterface::class);

$product = $productInterface->get('simple_option_50');
if ($product->getId()) {
    $productInterface->delete($product);
}

$product = $productInterface->get('simple_option_60');
if ($product->getId()) {
    $productInterface->delete($product);
}

$product = $productInterface->get('simple_option_70');
if ($product->getId()) {
    $productInterface->delete($product);
}

$product = $productInterface->get('simple_option_55');
if ($product->getId()) {
    $productInterface->delete($product);
}

$product = $productInterface->get('simple_option_59');
if ($product->getId()) {
    $productInterface->delete($product);
}

$product = $productInterface->get('simple_option_65');
if ($product->getId()) {
    $productInterface->delete($product);
}

$product = $productInterface->get('configurable_ws_1');
if ($product->getId()) {
    $productInterface->delete($product);
}

$product = $productInterface->get('configurable_ws_2');
if ($product->getId()) {
    $productInterface->delete($product);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture(
    'Magento_ConfigurableProductDataExporter::Test/_files/setup_configurable_attribute_rollback.php'
);
Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/setup_categories_rollback.php'
);
Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/setup_two_stores_and_two_websites_rollback.php'
);
