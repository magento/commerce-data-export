<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture(
    'Magento/CatalogDataExporter/_files/second_website_with_store_and_store_view_rollback.php'
);

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$productRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

$skus = [
    'bundle_dynamic_product_with_regular_price',
    'bundle_dynamic_product_with_special_price',
    'bundle_dynamic_product_with_tier_price'
];

foreach ($skus as $sku) {
    try {
        $product = $productRepository->get($sku, false, null, true);
        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        //Product already removed
    }
}

Resolver::getInstance()->requireDataFixture(
    'Magento_ProductPriceDataExporter::Test/_files/bundle_option_products_rollback.php'
);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
