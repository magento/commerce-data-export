<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
