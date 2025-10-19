<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */

/** @var Registry $registry */

use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

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
