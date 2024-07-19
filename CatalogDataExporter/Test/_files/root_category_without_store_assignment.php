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
declare(strict_types=1);

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$categoryFactory = $objectManager->get(CategoryFactory::class);
$categoryRepository = $objectManager->create(CategoryRepositoryInterface::class);

/** @var Category $rootCategory */
$rootCategory = $categoryFactory->create();
$rootCategory->isObjectNew(true);
$rootCategory->setName('Second Root Category')
    ->setParentId(Category::TREE_ROOT_ID)
    ->setIsActive(true)
    ->setPosition(2);
$rootCategory = $categoryRepository->save($rootCategory);
