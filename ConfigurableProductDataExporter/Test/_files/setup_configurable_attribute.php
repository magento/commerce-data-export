<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\TestFramework\Helper\Bootstrap;

$attributes = [
    'first_test_configurable',
    'second_test_configurable'
];

$objectManager = Bootstrap::getObjectManager();

$eavConfig = $objectManager->get(Config::class);

/** @var AttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->create(AttributeRepositoryInterface::class);

/** @var CategorySetup $installer */
$installer = $objectManager->create(CategorySetup::class);

$i = 0;
foreach ($attributes as $attributeName) {
    $attribute = $eavConfig->getAttribute('catalog_product', $attributeName);
    $eavConfig->clear();

    if (isset($attribute) && !$attribute->getId()) {
        /** @var Attribute $attribute */
        $attribute = $objectManager->create(Attribute::class);
        $attribute->setData(
            [
                'attribute_code' =>  $attributeName,
                'entity_type_id' => $installer->getEntityTypeId('catalog_product'),
                'is_global' => 1,
                'is_user_defined' => 1,
                'frontend_input' => 'select',
                'is_unique' => 0,
                'is_required' => 1,
                'is_searchable' => 0,
                'is_visible_in_advanced_search' => 0,
                'is_comparable' => 0,
                'is_filterable' => 0,
                'is_filterable_in_search' => 0,
                'is_used_for_promo_rules' => 0,
                'is_html_allowed_on_front' => 1,
                'is_visible_on_front' => 0,
                'used_in_product_listing' => 0,
                'used_for_sort_by' => 0,
                'frontend_label' => ['Test Configurable'],
                'backend_type' => 'int',
                'option' => [
                    'value' => [
                        'option_1' => ['Option 1'],
                        'option_2' => ['Option 2'],
                        'option_3' => ['Option 3'],
                        'option_4' => ['Option 4']
                    ],
                    'order' => ['option_1' => 1, 'option_2' => 2, 'option_3' => 3, 'option_4' => 4],
                ],
            ]
        );

        $attributeRepository->save($attribute);
    }


    $installer->addAttributeToGroup('catalog_product', 'Default', 'General', $attribute->getId());
}

$eavConfig->clear();
