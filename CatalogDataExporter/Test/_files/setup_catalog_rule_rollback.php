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

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Rule $catalogRuleResource */
$catalogRuleResource = $objectManager->create(Rule::class);

//Retrieve rule id by name
$select = $catalogRuleResource->getConnection()->select();
$select->from($catalogRuleResource->getMainTable(), 'rule_id');
$select->where('name = ?', 'test_catalog_rule');
$ruleId = $catalogRuleResource->getConnection()->fetchOne($select);

try {
    /** @var CatalogRuleRepositoryInterface $ruleRepository */
    $ruleRepository = $objectManager->create(CatalogRuleRepositoryInterface::class);
    $ruleRepository->deleteById($ruleId);
} catch (Exception $ex) {
    //Nothing to remove
}
/** @var IndexBuilder $indexBuilder */
$indexBuilder = $objectManager->get(IndexBuilder::class);
$indexBuilder->reindexFull();
