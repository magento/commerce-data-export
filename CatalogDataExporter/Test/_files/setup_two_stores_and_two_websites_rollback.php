<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Store\Model\Group;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Store\Model\Store;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Registry;

$objectManager = Bootstrap::getObjectManager();
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$store = $objectManager->create(Store::class);
$store->load('custom_store_view_one', 'code');
if ($store->getId()) {
    $store->delete();
}

$store2 = $objectManager->create(Store::class);
$store2->load('custom_store_view_two', 'code');
if ($store2->getId()) {
    $store2->delete();
}

/**
 * @var Group $storeGroup
 */
$storeGroup = $objectManager->create(Group::class);
$storeGroup->load('second_store_group', 'code');
$rootCategoryId = null;
if ($storeGroup->getId()) {
    $rootCategoryId = $storeGroup->getRootCategoryId();
    $storeGroup->delete();
}

if ($rootCategoryId) {
    $categoryRepository = $objectManager->create(CategoryRepositoryInterface::class);
    $category = $categoryRepository->get($rootCategoryId);
    if ($category->getId()) {
        $categoryRepository->delete($category);
    }
}
$website = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Store\Model\Website::class);
/** @var $website \Magento\Store\Model\Website */
$websiteId = $website->load('test', 'code')->getId();
if ($websiteId) {
    try {
        $website->getResource()->delete($website);
    } catch (Exception $e) {
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
