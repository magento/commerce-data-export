<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Catalog\Model\Category;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Create fixture categories
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
    ->setImage("http://localhost/media/catalog/category/image.jpg")
    ->setDescription('category description')
    ->setMetaTitle('Meta title')
    ->setMetaDescription('Meta description')
    ->setMetaKeywords('Meta keywords')
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setIncludeInMenu(true)
    ->setPosition(1)
    ->setStoreId(1)
    ->save();

// not include in menu for 2nd store view
$category
    ->setIncludeInMenu(false)
    ->setStoreId($storeId)
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
    ->setIncludeInMenu(false)
    ->setPosition(1)
    ->setStoreId(1)
    ->save();

// not enabled for 2nd store view
$category
    ->setIncludeInMenu(true)
    ->setIsActive(false)
    ->setStoreId($storeId)
    ->save();


/** @var $category Category */
$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category->setId(300)
    ->setName('SaaS Category Sub - Sub')
    ->setParentId(200)
    ->setPath('1/2/100/200/300')
    ->setUrlKey('saas-category-sub-sub')
    ->setLevel(4)
    ->setAvailableSortBy(['name', 'price'])
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setIncludeInMenu(true)
    ->setPosition(1)
    ->setStoreId(1)
    ->save();

// Do not save for "fixture_second_store" - should use settings from global (0) store
