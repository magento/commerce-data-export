<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\Category;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Create fixture categories
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Store $store */
$store = $objectManager->create(Store::class);
$storeId = $store->load('fixture_second_store', 'code')->getId();

/** @var $category Category */
$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category->setId(100)
    ->setName('SaaS Category')
    ->setParentId(2)
    ->setPath('1/2/100')
    ->setUrlKey('saas-category')
    ->setLevel(2)
    ->setAvailableSortBy(['name', 'price'])
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->setStoreId(1)
    ->save();

/** @var $category Category */
$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category->setId(200)
    ->setName('SaaS Category Sub')
    ->setParentId(100)
    ->setPath('1/2/100/200')
    ->setUrlKey('saas-category-sub')
    ->setLevel(3)
    ->setAvailableSortBy(['name', 'price'])
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->setStoreId(1)
    ->save();
