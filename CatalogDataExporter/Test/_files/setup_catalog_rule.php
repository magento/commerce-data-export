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

use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\CatalogRule\Model\Rule;
use Magento\Customer\Model\GroupManagement;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Creates simple Catalog Rule with the following data:
 * active, applied to all products, without time limits, with 10% off for Not Logged In Customers
 */

/** @var $banner Rule */
$catalogRule = Bootstrap::getObjectManager()->create(
    Rule::class
);

$catalogRule->setId(1000)
    ->setIsActive(1)
    ->setName('test_catalog_rule')
    ->setCustomerGroupIds(GroupManagement::NOT_LOGGED_IN_ID)
    ->setDiscountAmount(5)
    ->setWebsiteIds([0 => 1])
    ->setSimpleAction('by_percent')
    ->setStopRulesProcessing(false)
    ->setSortOrder(0)
    ->setSubIsEnable(0)
    ->setSubDiscountAmount(0)
    ->save();

/** @var IndexBuilder $indexBuilder */
$indexBuilder = Bootstrap::getObjectManager()
    ->get(IndexBuilder::class);
$indexBuilder->reindexFull();
