<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Store\Api\WebsiteRepositoryInterface;

$websiteRepository = Bootstrap::getObjectManager()->get(WebsiteRepositoryInterface::class);
$defaultCategoryId = $websiteRepository->get('base')->getDefaultStore()->getRootCategoryId();
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
        ->setRootCategoryId($defaultCategoryId)
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
