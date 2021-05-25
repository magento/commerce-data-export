<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Store $store */
$store = $objectManager->create(Store::class);
if (!$store->load('fixture_second_store', 'code')->getId()) {
    $websiteId = $objectManager->get(StoreManagerInterface::class)
        ->getWebsite()
        ->getId();
    $groupId =$objectManager->get(StoreManagerInterface::class)->getWebsite()->getDefaultGroupId();
    $store->setCode('fixture_second_store')->setWebsiteId($websiteId)
        ->setGroupId($groupId)
        ->setName('Fixture Store')
        ->setSortOrder(10)
        ->setIsActive(1);
    $store->save();
}
