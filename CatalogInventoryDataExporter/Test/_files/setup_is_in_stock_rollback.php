<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

try {
    /** @var ProductRepositoryInterface $productInterface */
    $productInterface = $objectManager->create(ProductRepositoryInterface::class);
    $product = $productInterface->get('simple5');
    if ($product->getId()) {
        $productInterface->delete($product);
    }
} catch (Exception $e) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
