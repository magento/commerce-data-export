<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\Entity\Attribute;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Set $attributeSet */
$attributeSet = $objectManager->create(Set::class);
$attributeSet->load('SaaSCatalogAttributeSet', 'attribute_set_name');

if (!$attributeSet->getId()) {
    /** @var Set $attributeSet */
    $attributeSet = $objectManager->create(Set::class);

    /** @var Type $entityType */
    $entityType = $objectManager->create(Type::class)->loadByCode('catalog_product');
    $defaultSetId = $objectManager->create(Product::class)->getDefaultAttributeSetid();

    $data = [
        'attribute_set_name' => 'SaaSCatalogAttributeSet',
        'entity_type_id' => $entityType->getId(),
        'sort_order' => 300,
    ];

    $attributeSet->setData($data);
    $attributeSet->validate();
    $attributeSet->save();
    $attributeSet->initFromSkeleton($defaultSetId);
    $attributeSet->save();

    $attributeSetInfo = [
        'entity_type_id' => $entityType->getId(),
        'attribute_set_id' => $attributeSet->getId(),
        'attribute_group_id' => $attributeSet->getDefaultGroupId()
    ];

    $attributesDefinitions = [
        'custom_label' => [
            'attribute_code' => 'custom_label',
            'frontend_input' => 'text',
            'backend_type' => 'varchar',
            'is_required' => 0,
            'is_user_defined' => 1,
        ],
        'custom_description' => [
            'attribute_code' => 'custom_description',
            'frontend_input' => 'text',
            'backend_type' => 'text',
            'is_required' => 0,
            'is_user_defined' => 1,
        ],
        'custom_select' => [
            'attribute_code'                => 'custom_select',
            'entity_type_id'                => $entityType->getId(),
            'is_global'                     => 0,
            'is_user_defined'               => 1,
            'frontend_input'                => 'select',
            'is_unique'                     => 0,
            'is_required'                   => 0,
            'is_searchable'                 => 0,
            'is_visible_in_advanced_search' => 0,
            'is_comparable'                 => 0,
            'is_filterable'                 => 0,
            'is_filterable_in_search'       => 0,
            'is_used_for_promo_rules'       => 0,
            'is_html_allowed_on_front'      => 1,
            'is_visible_on_front'           => 1,
            'used_in_product_listing'       => 1,
            'used_for_sort_by'              => 0,
            'frontend_label'                => ['Drop-Down Attribute'],
            'backend_type'                  => 'varchar',
            'backend_model'                 => ArrayBackend::class,
            'option'                        => [
                'value' => [
                    'option_1' => ['Option 1'],
                    'option_2' => ['Option 2'],
                    'option_3' => ['Option 3'],
                ],
                'order' => [
                    'option_1' => 1,
                    'option_2' => 2,
                    'option_3' => 3,
                ],
            ],
        ],
        'yes_no_attribute' => [
            'attribute_code' => 'yes_no_attribute',
            'entity_type_id' => $entityType->getId(),
            'is_global' => 0,
            'is_user_defined' => 1,
            'frontend_input' => 'boolean',
            'is_unique' => 0,
            'is_required' => 0,
            'is_searchable' => 1,
            'is_visible_in_advanced_search' => 1,
            'is_comparable' => 0,
            'is_filterable' => 1,
            'is_filterable_in_search' => 1,
            'is_used_for_promo_rules' => 0,
            'is_html_allowed_on_front' => 1,
            'is_visible_on_front' => 1,
            'used_in_product_listing' => 1,
            'used_for_sort_by' => 0,
            'frontend_label' => ['Boolean Attribute'],
            'backend_type' => 'int',
            'source_model' => Magento\Eav\Model\Entity\Attribute\Source\Boolean::class
        ]
    ];

    foreach ($attributesDefinitions as $attributesDefinition) {
        /** @var Attribute $attribute */
        // phpcs:ignore Magento2.Performance.ForeachArrayMerge
        $attributeData = array_merge($attributesDefinition, $attributeSetInfo);
        $attribute = $objectManager->create(Attribute::class);
        $attribute->setData($attributeData);
        $attribute->save();
    }
}
