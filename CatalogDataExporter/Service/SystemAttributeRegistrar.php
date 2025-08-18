<?php
/**
 * Copyright 2025 Adobe
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

namespace Magento\CatalogDataExporter\Service;

use Magento\Catalog\Model\Product;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Service responsible for creating a product attribute if it does not exist
 * Created attribute is not visible in admin grid and cannot be altered by customer
 */
class SystemAttributeRegistrar
{
    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CommerceDataExportLoggerInterface $logger,
        private Config $eavConfig
    ) {}

    /**
     * @param string $attributeCode
     * @param $label
     * @param $properties
     * @return bool
     */
    public function execute(string $attributeCode, $label, $properties = []): bool
    {
        if ($this->exists($attributeCode)) {
            return true;
        }

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->getEntityTypeId(Product::ENTITY);
        $defaultProperties =             [
            'type' => 'text',
            'label' => $label,
            'note' => 'System attribute to carry specific product data to SaaS',
            'input' => 'textarea',
            'class' => '',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => false,
            'required' => false,
            'user_defined' => false,
            'default' => '',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'visible_in_advanced_search' => false,
            'used_in_product_listing' => false,
            'unique' => false,
            'is_used_in_grid' => false,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false,
        ];

        try {
            $eavSetup->addAttribute(
                Product::ENTITY,
                $attributeCode,
                array_merge($defaultProperties, $properties)
            );
        } catch (\Throwable $e) {
            $this->logger->warning(
                sprintf(
                    'Failed to create attribute "%s". Error: %s',
                    $attributeCode,
                    $e->getMessage()
                )
            );
            return false;
        }

        return true;
    }

    /**
     * @param string $attributeCode
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function exists(string $attributeCode): bool
    {
        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('eav_attribute');
        $select = $connection->select()
            ->from($tableName, 'attribute_id')
            ->where('attribute_code = :attribute_code')
            ->where('entity_type_id = ?', $this->getEntityTypeId());
        $bind = ['attribute_code' => $attributeCode];
        return (bool) $connection->fetchOne($select, $bind);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getEntityTypeId(): int
    {
        return (int)$this->eavConfig->getEntityType(Product::ENTITY)->getId();
    }
}
