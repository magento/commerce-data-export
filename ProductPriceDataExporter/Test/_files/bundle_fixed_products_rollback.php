<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/second_website_with_store_and_store_view_rollback.php'
);

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$productRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

$skus = [
    'bundle_fixed_product_with_regular_price',
    'bundle_fixed_product_with_special_price',
    'bundle_fixed_product_with_tier_price'
];

foreach ($skus as $sku) {
    try {
        $product = $productRepository->get($sku, false, null, true);
        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException) {
        //Product already removed
    }
}

Resolver::getInstance()->requireDataFixture(
    'Magento_ProductPriceDataExporter::Test/_files/bundle_option_products.php'
);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
