<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
} catch (Exception) {
    //Nothing to remove
}
/** @var IndexBuilder $indexBuilder */
$indexBuilder = $objectManager->get(IndexBuilder::class);
$indexBuilder->reindexFull();
