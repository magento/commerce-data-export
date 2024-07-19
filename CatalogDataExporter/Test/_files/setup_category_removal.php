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

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$defaultStore = $storeManager->getStore('default');

$categoryFactory = $objectManager->get(CategoryFactory::class);

/** @var Category $category */
$category = $categoryFactory->create();
$category->isObjectNew(true);
$category
    ->setId(600)
    ->setName('Category removal test')
    ->setParentId(2)
    ->setPath('1/2/600')
    ->setLevel(2)
    ->setIsActive(1)
    ->setPosition(1)
    ->setStoreId($defaultStore->getId())
    ->save();
