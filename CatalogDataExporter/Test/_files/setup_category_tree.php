<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_two_stores.php');
Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_category_image.php');

$objectManager = Bootstrap::getObjectManager();

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);

$currentStore = $storeManager->getStore();
$storeCustomOne = $storeManager->getStore('custom_store_view_one');
$storeCustomTwo = $storeManager->getStore('custom_store_view_two');

$categoryCollectionFactory = $objectManager->get(CollectionFactory::class);
/** @var Collection $categoryCollection */
$categoryCollection = $categoryCollectionFactory->create();
$rootCategory = $categoryCollection
    ->addAttributeToFilter(CategoryInterface::KEY_NAME, 'Second Root Category')
    ->setPageSize(1)
    ->getFirstItem();

$customStoresCategories = [
    [
        'id' => 400,
        'name' => 'Category 1',
        'parent_id' => $rootCategory->getId(),
        'path' => "1/{$rootCategory->getId()}/400",
        'level' => 2,
        'available_sort_by' => ['name'],
        'default_sort_by' => 'name',
        'is_active' => true,
        'include_in_menu' => true,
        'position' => 1,
        'url_key' => 'category_1',
        'image' => 'category_test_image.jpg'
    ],
    [
        'id' => 401,
        'name' => 'Category 1.1',
        'parent_id' => 400,
        'path' => "1/{$rootCategory->getId()}/400/401",
        'level' => 3,
        'available_sort_by' => ['name'],
        'default_sort_by' => 'name',
        'is_active' => true,
        'include_in_menu' => true,
        'position' => 2,
        'url_key' => 'category_1_1',
        'image' => 'category_test_image.jpg'
    ],
    [
        'id' => 402,
        'name' => 'Category 1.1.1',
        'parent_id' => 401,
        'path' => "1/{$rootCategory->getId()}/400/401/402",
        'level' => 4,
        'available_sort_by' => ['name', 'price'],
        'default_sort_by' => 'price',
        'is_active' => false,
        'include_in_menu' => true,
        'position' => 4,
        'url_key' => 'category_1_1_1',
        'image' => 'category_test_image.jpg'
    ],
];

$mainStoreCategories = [
    [
        'id' => 500,
        'name' => 'Category main 1',
        'parent_id' => 2,
        'path' => '1/2/500',
        'level' => 2,
        'available_sort_by' => ['name'],
        'default_sort_by' => 'name',
        'is_active' => true,
        'include_in_menu' => true,
        'position' => 1,
        'url_key' => 'category_main_1',
        'image' => 'category_test_image.jpg'
    ],
    [
        'id' => 501,
        'name' => 'Category main 1.1',
        'parent_id' => 500,
        'path' => '1/2/500/501',
        'level' => 3,
        'available_sort_by' => ['name'],
        'default_sort_by' => 'name',
        'is_active' => true,
        'include_in_menu' => false,
        'position' => 5,
        'url_key' => 'category_main_1_1',
        'image' => 'category_test_image.jpg'
    ],
    [
        'id' => 502,
        'name' => 'Category main 1.1.1',
        'parent_id' => 501,
        'path' => '1/2/500/501/502',
        'level' => 4,
        'available_sort_by' => ['name', 'price'],
        'default_sort_by' => 'price',
        'is_active' => true,
        'include_in_menu' => true,
        'position' => 4,
        'url_key' => 'category_main_1_1_1',
        'image' => 'category_test_image.jpg'
    ],
];

$categoryFactory = $objectManager->get(CategoryFactory::class);
$categoryRepository = $objectManager->create(CategoryRepositoryInterface::class);

foreach (\array_merge($mainStoreCategories, $customStoresCategories) as $data) {
    /** @var Category $category */
    $category = $categoryFactory->create();
    $category->isObjectNew(true);
    $category
        ->setId($data['id'])
        ->setName($data['name'])
        ->setParentId($data['parent_id'])
        ->setPath($data['path'])
        ->setLevel($data['level'])
        ->setAvailableSortBy($data['available_sort_by'])
        ->setDefaultSortBy($data['default_sort_by'])
        ->setIsActive($data['is_active'])
        ->setIncludeInMenu($data['include_in_menu'])
        ->setPosition($data['position'])
        ->setStoreId(0)
        ->setImage($data['image'])
        ->save();
}

// Build url_path for each category per store, tracking parent url_path so child paths
// are computed correctly. url_path must be kept in sync with url_key here because
// CategoryRepository::save() does not automatically update url_path when only url_key
// changes for a store view.
foreach ([$storeCustomOne, $storeCustomTwo] as $store) {
    $urlPathByParentId = []; // parent_id => url_path built so far for this store

    foreach ($customStoresCategories as $data) {
        $storeManager->setCurrentStore($store);
        $category = $categoryRepository->get($data['id'], $store->getId());

        $urlKey = \sprintf('%s_%s', $category->getUrlKey(), $store->getCode());
        $parentUrlPath = $urlPathByParentId[$data['parent_id']] ?? '';
        $urlPath = $parentUrlPath !== '' ? $parentUrlPath . '/' . $urlKey : $urlKey;

        $category->setName(\sprintf('%s_%s', $category->getName(), $store->getCode()))
            ->setUrlKey($urlKey)
            ->setUrlPath($urlPath);

        $categoryRepository->save($category);
        $urlPathByParentId[$data['id']] = $urlPath;
    }
}

// Set store-specific overrides for categories 500/501 in custom_store_view_one.
// This drives the ancestor-propagation test:
//   500 has includeInMenu=false in this store → propagates to all descendants
//   501 has isActive=false in this store (own store-specific value)
$storeManager->setCurrentStore($storeCustomOne);
$category500 = $categoryRepository->get(500, $storeCustomOne->getId());
$category500->setIncludeInMenu(false);
$categoryRepository->save($category500);

$category501 = $categoryRepository->get(501, $storeCustomOne->getId());
$category501->setIsActive(false);
$categoryRepository->save($category501);

$storeManager->setCurrentStore($currentStore);
