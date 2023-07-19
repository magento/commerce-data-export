<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
