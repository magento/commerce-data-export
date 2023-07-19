<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
