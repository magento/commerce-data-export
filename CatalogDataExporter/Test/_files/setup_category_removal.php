<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
