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

use Magento\Downloadable\Api\DomainManagerInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var DomainManagerInterface $domainManager */
$domainManager = $objectManager->get(DomainManagerInterface::class);
$domainManager->removeDomains(['sampleurl.com']);

Resolver::getInstance()->requireDataFixture('Magento/Downloadable/_files/product_downloadable_rollback.php');
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

try {
    $product = $productRepository->get('downloadable-product');
    $productRepository->delete($product);
} catch (NoSuchEntityException $e) {
    //Product already removed
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_two_stores_and_two_websites_rollback.php'
);
