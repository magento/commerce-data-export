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

try {
    /** @var ProductRepositoryInterface $productInterface */
    $productInterface = $objectManager->create(ProductRepositoryInterface::class);
    $product = $productInterface->get('simple1');
    if ($product->getId()) {
        $productInterface->delete($product);
    }

    $product = $productInterface->get('simple2');
    if ($product->getId()) {
        $productInterface->delete($product);
    }

    $product = $productInterface->get('simple3');
    if ($product->getId()) {
        $productInterface->delete($product);
    }
} catch (Exception $e) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_two_stores_and_two_websites_rollback.php'
);
