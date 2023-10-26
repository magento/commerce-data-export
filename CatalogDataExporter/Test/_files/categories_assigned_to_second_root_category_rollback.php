<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Create fixture categories
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var @var Magento\Catalog\Api\CategoryRepositoryInterface $categoryInterface */
$categoryInterface = $objectManager->create(Magento\Catalog\Api\CategoryRepositoryInterface::class);
/** @var CategoryListInterface $repository */
$repository = Bootstrap::getObjectManager()->get(CategoryListInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('name', ['Test Category', 'Test Category Sub'], 'in')
    ->create();
$items = $repository->getList($searchCriteria)->getItems();

try {
    foreach ($items as $category) {
        if ($category->getId()) {
            $categoryInterface->delete($category);
        }
    }
} catch (Exception $e) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
