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
} catch (Exception $e) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
