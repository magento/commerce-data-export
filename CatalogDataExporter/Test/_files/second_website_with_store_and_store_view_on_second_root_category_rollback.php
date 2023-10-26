<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
