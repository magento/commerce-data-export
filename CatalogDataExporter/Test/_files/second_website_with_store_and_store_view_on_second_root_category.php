<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\CategoryListInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture(
    'Magento_CatalogDataExporter::Test/_files/root_category_without_store_assignment.php'
);

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('name', 'Second Root Category')
    ->create();
/** @var CategoryListInterface $repository */
$repository = Bootstrap::getObjectManager()->get(CategoryListInterface::class);
$items = $repository->getList($searchCriteria)
    ->getItems();
$category =  array_pop($items);
$categoryId = $category->getId();

$websiteRepository = Bootstrap::getObjectManager()->get(WebsiteRepositoryInterface::class);
/** @var $website \Magento\Store\Model\Website */
$website = Bootstrap::getObjectManager()->create(\Magento\Store\Model\Website::class);

if (!$website->load('test', 'code')->getId()) {
    $website->setData(['code' => 'test', 'name' => 'Test Website', 'default_group_id' => '1', 'is_default' => '0']);
    $website->save();
}
$websiteId = $website->getId();
$store = Bootstrap::getObjectManager()->create(\Magento\Store\Model\Store::class);
if (!$store->load('fixture_second_store', 'code')->getId()) {
    // Workaround to add store group to second website
    $storeGroup = Bootstrap::getObjectManager()
        ->create(\Magento\Store\Model\Group::class);
    $storeGroup->setCode('second_store_group')
        ->setName('Second Store Group')
        ->setRootCategoryId($categoryId)
        ->setWebsite($website);
    try {
        $storeGroup->save();
    } catch (Exception $e) {
    }

    $store->setCode('fixture_second_store')
        ->setGroupId($storeGroup->getId())
        ->setWebsiteId($websiteId)
        ->setName('Fixture Second Store')
        ->setSortOrder(10)->setIsActive(1);
    $store->save();
}

/* Refresh CatalogSearch index */
/** @var \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry */
$indexerRegistry = Bootstrap::getObjectManager()
    ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
$indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->reindexAll();
