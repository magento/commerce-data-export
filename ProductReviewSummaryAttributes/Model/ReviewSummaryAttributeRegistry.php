<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Model;

use Magento\Catalog\Model\Product;
use Magento\CatalogDataExporter\Service\SystemAttributeRegistrarFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Creates the review summary system attributes on demand and provides their product attributes feed overrides.
 *
 * Unlike the shared SystemAttributeRegistrar configuration (attributes created for every merchant on the first
 * products feed sync), these attributes are created only when review summaries are actually exported.
 * The registrar is instantiated with this class' configurationMap only, so it never creates other system
 * attributes and other modules never create these ones.
 */
class ReviewSummaryAttributeRegistry
{
    /**
     * @var bool
     */
    private bool $registered = false;

    /**
     * @param SystemAttributeRegistrarFactory $systemAttributeRegistrarFactory
     * @param ResourceConnection $resourceConnection
     * @param EavConfig $eavConfig
     * @param array $configurationMap See SystemAttributeRegistrar for the format
     */
    public function __construct(
        private readonly SystemAttributeRegistrarFactory $systemAttributeRegistrarFactory,
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig,
        private readonly array $configurationMap = []
    ) {
    }

    /**
     * Get codes of the review summary attributes.
     *
     * @return string[]
     */
    public function getAttributeCodes(): array
    {
        return array_keys($this->configurationMap);
    }

    /**
     * Get product attributes feed overrides for the given attribute code.
     *
     * @param string $attributeCode
     * @return array
     */
    public function getExporterOverride(string $attributeCode): array
    {
        return $this->configurationMap[$attributeCode]['exporterOverrides'] ?? [];
    }

    /**
     * Check whether all review summary attributes exist in the EAV storage.
     *
     * An attribute with the same code created by a merchant (user defined, or with a different type) is not
     * considered registered, so no review summary values are exported to it.
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isRegistered(): bool
    {
        if ($this->registered) {
            return true;
        }
        $attributeCodes = $this->getAttributeCodes();
        if (empty($attributeCodes)) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                $this->resourceConnection->getTableName('eav_attribute'),
                ['attribute_code', 'is_user_defined', 'backend_type', 'frontend_input']
            )
            ->where('attribute_code IN (?)', $attributeCodes)
            ->where('entity_type_id = ?', (int)$this->eavConfig->getEntityType(Product::ENTITY)->getId());
        $ownAttributes = array_filter(
            $connection->fetchAll($select),
            fn (array $attribute) => $this->isOwnAttribute($attribute)
        );
        $this->registered = count($ownAttributes) === count($attributeCodes);

        return $this->registered;
    }

    /**
     * Check that the stored attribute is the system attribute described in the configuration map.
     *
     * @param array $attribute
     * @return bool
     */
    private function isOwnAttribute(array $attribute): bool
    {
        $properties = $this->configurationMap[$attribute['attribute_code']]['properties'] ?? [];
        if ((int)$attribute['is_user_defined'] !== 0) {
            return false;
        }
        foreach (['type' => 'backend_type', 'input' => 'frontend_input'] as $property => $column) {
            if (isset($properties[$property]) && $attribute[$column] !== $properties[$property]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create missing review summary attributes.
     *
     * @return void
     * @throws LocalizedException
     */
    public function register(): void
    {
        if (empty($this->configurationMap)) {
            return;
        }
        $this->systemAttributeRegistrarFactory
            ->create(['configurationMap' => $this->configurationMap])
            ->execute();
    }
}
