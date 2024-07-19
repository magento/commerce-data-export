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

use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Set;
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
    /** @var Set $attributeSet */
    $attributeSet = $objectManager->create(Set::class);
    $attributeSet->load('SaaSCatalogAttributeSet', 'attribute_set_name');
    if ($attributeSet->getId()) {
        $attributeSet->delete();
    }

    /** @var Attribute $attribute */
    $attribute = $objectManager->create(Attribute::class);
    $attribute->load('custom_label', 'attribute_code');
    if ($attribute->getId()) {
        $attribute->delete();
    }

    $attribute->load('custom_description', 'attribute_code');
    if ($attribute->getId()) {
        $attribute->delete();
    }

    $attribute->load('custom_select', 'attribute_code');
    if ($attribute->getId()) {
        $attribute->delete();
    }

    $attribute->load('yes_no_attribute', 'attribute_code');
    if ($attribute->getId()) {
        $attribute->delete();
    }
} catch (Exception $e) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
