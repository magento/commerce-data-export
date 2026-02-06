<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

try {
    /** @var Attribute $attribute */
    $attribute = $objectManager->create(Attribute::class);
    $attribute->load('first_test_configurable', 'attribute_code');
    if ($attribute->getId()) {
        $attribute->delete();
    }
    $attribute->load('second_test_configurable', 'attribute_code');
    if ($attribute->getId()) {
        $attribute->delete();
    }
} catch (Exception) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
