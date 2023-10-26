<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Create fixture categories
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
$categoryFactory = Bootstrap::getObjectManager()->get(CategoryFactory::class);
$categoryRepository = Bootstrap::getObjectManager()->create(CategoryRepositoryInterface::class);

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('name', 'Second Root Category')
    ->create();
/** @var CategoryListInterface $repository */
$repository = Bootstrap::getObjectManager()->get(CategoryListInterface::class);
$items = $repository->getList($searchCriteria)
    ->getItems();
/** @var Category $rootCategory */
$rootCategory =  array_pop($items);

/** @var Category $category1 */
$category1 = $categoryFactory->create();
$category1->isObjectNew(true);
$category1->setName('Test Category')
    ->setParentId($rootCategory->getId())
    ->setUrlKey('test-category')
    ->setLevel(2)
    ->setAvailableSortBy(['name', 'price'])
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1);
$categoryRepository->save($category1);

/** @var $category2 Category */
$category2 = $categoryFactory->create();
$category2->isObjectNew(true);
$category2->setName('Test Category Sub')
    ->setParentId($category1->getId())
    ->setUrlKey('test-category-sub')
    ->setLevel(3)
    ->setAvailableSortBy(['name', 'price'])
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1);
$categoryRepository->save($category2);
