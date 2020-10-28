<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use \Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

try {
    $storeResource = $objectManager->create(StoreResource::class);
    /** @var Store $store */
    $store = $objectManager->create(Store::class);
    $store->load('fixture_second_store', 'code');
    if ($store->getId()) {
        $storeResource->delete($store);
    }
} catch (Exception $e) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
