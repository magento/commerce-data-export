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
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Service responsible for creating a product system attribute in the AC DB.
 * Created attribute is not visible in admin grid and cannot be altered by customer - it's used only for extending
 * product feed with additional data
 *
 * Attribute configuration is provided through the constructor argument $configurationMap with the following structure:
 * [
 *   "{attribute code}" => [
 *           "properties" => [
 *                   "label" => "..."
 *                   ... other properties for the attribute that would be persisted to DB
 *           ],
 *          "exporterOverrides" => [
 *                  "visible" => true,
 *                  ... other fields that should be overwritten, see et_schema.xml for "productAttributes"
 *          ]
 *     ]
 *  ]
 */
class SystemAttributeRegistrar
{
    private array $configurationMap;

    private array $attributeCodesToRegister;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CommerceDataExportLoggerInterface $logger
     * @param Config $eavConfig
     * @param array $configurationMap
     * @throws LocalizedException
     */
    public function __construct(
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CommerceDataExportLoggerInterface $logger,
        private readonly Config $eavConfig,
        array $configurationMap
    ) {
        $this->validateConfiguration($configurationMap);
        $this->configurationMap = $configurationMap;
        $this->attributeCodesToRegister = !empty($configurationMap) ? array_keys($configurationMap) : [];
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function execute(): void
    {
        $attributeCodes = $this->getAttributeCodes();
        $attributeCodes = array_diff($attributeCodes, $this->getRegisteredAttributes($attributeCodes));

        if (empty($attributeCodes)) {
            return ;
        }
        $properties = $this->buildAttributeProperties($attributeCodes);
        foreach ($attributeCodes as $attributeCode) {
            $this->createAttribute($attributeCode, $properties[$attributeCode] ?? []);
        }
    }

    /**
     * @return array
     */
    public function getAttributeCodes(): array
    {
        return $this->attributeCodesToRegister;
    }

    /**
     * Get overrides for product attributes feed for given attribute code
     *
     * @param string $attributeCode
     * @return array
     */
    public function getExporterOverride(string $attributeCode): array
    {
        return $this->configurationMap[$attributeCode]['exporterOverrides'] ?? [];
    }

    /**
     * @param array $attributeCodes
     * @return array
     * @throws LocalizedException
     */
    private function getRegisteredAttributes(array $attributeCodes): array
    {
        if (empty($attributeCodes)) {
            return [];
        }

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('eav_attribute');
        $select = $connection->select()
            ->from($tableName, 'attribute_code')
            ->where('attribute_code IN (?)', $attributeCodes)
            ->where('entity_type_id = ?', $this->getEntityTypeId());
        return $connection->fetchCol($select);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getEntityTypeId(): int
    {
        return (int)$this->eavConfig->getEntityType(Product::ENTITY)->getId();
    }

    /**
     * @param $attributeCode
     * @param array $properties
     * @return void
     * @throws LocalizedException
     */
    private function createAttribute($attributeCode, array $properties): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->getEntityTypeId(Product::ENTITY);
        $defaultProperties = [
            'type' => 'static',
            'label' => $attributeCode,
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
        }
    }

    /**
     * @param $configurationMap
     * @return void
     * @throws LocalizedException
     */
    private function validateConfiguration(&$configurationMap): void
    {
        if (empty($configurationMap)) {
            return ;
        }

        foreach ($configurationMap as $attributeCode => &$config) {
            if (!isset($config['properties']) || !is_array($config['properties'])) {
                throw new LocalizedException(
                    __('Properties for attribute "%1" are not defined.', $attributeCode)
                );
            }
            if (!isset($config['properties']['label']) || !is_string($config['properties']['label'])) {
                throw new LocalizedException(
                    __('Label for attribute "%1" is not defined in properties', $attributeCode)
                );
            }
            if (!isset($config['exporterOverrides'])) {
                $config['exporterOverrides'] = [];
            } elseif (!is_array($config['exporterOverrides'])) {
                throw new LocalizedException(
                    __('Exporter overrides for attribute "%1" are not defined.', $attributeCode)
                );
            }
        }
    }

    /**
     * @param string $attributeCode
     * @return array
     */
    private function getProperties(string $attributeCode): array
    {
        return $this->configurationMap[$attributeCode]['properties'] ?? [];
    }

    /**
     * @param array $attributeCodes
     * @return array
     */
    private function buildAttributeProperties(array $attributeCodes): array
    {
        $properties = [];
        foreach ($attributeCodes as $attributeCode) {
            $attributeProperties = $this->getProperties($attributeCode);
            if ($attributeProperties) {
                $properties[$attributeCode] = $attributeProperties;
            }
        }
        return $properties;
    }
}
