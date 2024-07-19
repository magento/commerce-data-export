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

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var CollectionFactory $categoryCollectionFactory */
$categoryCollectionFactory = $objectManager->get(CollectionFactory::class);
/** @var CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $objectManager->create(CategoryRepositoryInterface::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$categoryCollection = $categoryCollectionFactory->create();
$categoryCollection->addAttributeToFilter(
    CategoryInterface::KEY_NAME,
    ['nin' => ['Root Catalog', 'Default Category', 'Second Root Category']]
);

foreach ($categoryCollection as $category) {
    $categoryRepository->delete($category);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_category_image_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento_CatalogDataExporter::Test/_files/setup_two_stores_rollback.php');
