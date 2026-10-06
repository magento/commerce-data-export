<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as CatalogAttribute;
use Magento\DataExporter\Test\Integration\DisablesFeedReadinessCheckers;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Verifies numeric product attribute metadata is exported with numeric=true.
 *
 * @magentoAppArea adminhtml
 */
class ProductNumericAttributeExportTest extends TestCase
{
    use DisablesFeedReadinessCheckers;

    private const ATTRIBUTE_CODE = 'numeric_export_test';

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testNumericAttributeMetadataIsExported(): void
    {
        self::disableFeedReadinessCheckers();
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->configure([
            'Magento\CatalogDataExporter\Model\Indexer\ProductAttributeFeedIndexMetadata' => [
                'arguments' => ['persistExportedFeed' => true],
            ],
        ]);

        $entityType = $objectManager->create(Type::class)->loadByCode('catalog_product');
        $attributeSetId = (int)$entityType->getDefaultAttributeSetId();

        /** @var Set $attributeSet */
        $attributeSet = $objectManager->create(Set::class)->load($attributeSetId);
        /** @var CatalogAttribute $attribute */
        $attribute = $objectManager->create(CatalogAttribute::class);
        $attribute->setData([
            'attribute_code' => self::ATTRIBUTE_CODE,
            'entity_type_id' => (int)$entityType->getId(),
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => (int)$attributeSet->getDefaultGroupId(),
            'is_global' => 1,
            'is_user_defined' => 1,
            'frontend_input' => 'numeric',
            'backend_type' => $attribute->getBackendTypeByInput('numeric'),
            'is_required' => 0,
            'is_unique' => 0,
            'is_searchable' => 0,
            'is_visible_in_advanced_search' => 0,
            'is_comparable' => 0,
            'is_filterable' => 0,
            'is_filterable_in_search' => 0,
            'is_used_for_promo_rules' => 0,
            'is_html_allowed_on_front' => 0,
            'is_visible_on_front' => 1,
            'used_in_product_listing' => 1,
            'used_for_sort_by' => 0,
            'frontend_label' => ['Numeric Export Test'],
        ]);
        $attribute->save();

        $objectManager->create(Indexer::class)
            ->load('catalog_data_exporter_product_attributes')
            ->reindexList([(int)$attribute->getId()]);

        /** @var ResourceConnection $resource */
        $resource = $objectManager->get(ResourceConnection::class);
        /** @var Json $jsonSerializer */
        $jsonSerializer = $objectManager->get(Json::class);
        $connection = $resource->getConnection();
        $select = $connection->select()
            ->from($resource->getTableName('cde_product_attributes_feed'), ['feed_data'])
            ->where('source_entity_id = ?', (int)$attribute->getId())
            ->where('is_deleted = ?', 0);
        $feedData = $connection->fetchOne($select);

        $this->assertIsString($feedData, 'Numeric attribute metadata was not exported.');
        $metadata = $jsonSerializer->unserialize($feedData);
        $this->assertSame(self::ATTRIBUTE_CODE, $metadata['attributeCode']);
        $this->assertSame('numeric', $metadata['frontendInput']);
        $this->assertSame('decimal', $metadata['dataType']);
        $this->assertTrue($metadata['numeric']);
    }
}
