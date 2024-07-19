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
