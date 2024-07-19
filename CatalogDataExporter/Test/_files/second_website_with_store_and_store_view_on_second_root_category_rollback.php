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

/** @var Registry $registry */

use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$registry = Bootstrap::getObjectManager()->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
$store = Bootstrap::getObjectManager()->create(Store::class);
if ($store->load('fixture_second_store', 'code')->getId()) {
    try {
        $store->delete();
        $store->getGroup()->delete();
    } catch (Exception $e) {
    }
}

$website = Bootstrap::getObjectManager()->create(Website::class);
/** @var $website Website */
$websiteId = $website->load('test', 'code')->getId();
if ($websiteId) {
    $website->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/root_category_without_store_assignment.php'
);
