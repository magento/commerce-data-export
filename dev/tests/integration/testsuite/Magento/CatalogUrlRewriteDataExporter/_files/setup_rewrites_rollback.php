<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\UrlRewrite;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

try {
    $rewrite = $objectManager->create(UrlRewrite::class);
    $rewrite->load('simple-product1.html', 'request_path');
    if ($rewrite->getId()) {
        $rewrite->delete();
    }

    $rewrite = $objectManager->create(UrlRewrite::class);
    $rewrite->load('simple-product2.html', 'request_path');
    if ($rewrite->getId()) {
        $rewrite->delete();
    }

    $rewrite = $objectManager->create(UrlRewrite::class);
    $rewrite->load('simple-product3.html', 'request_path');
    if ($rewrite->getId()) {
        $rewrite->delete();
    }
} catch (Exception $e) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture(
    'Magento/CatalogDataExporter/_files/setup_simple_products_rollback.php'
);
Resolver::getInstance()->requireDataFixture('Magento/CatalogDataExporter/_files/setup_categories_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/CatalogDataExporter/_files/setup_stores_rollback.php');
