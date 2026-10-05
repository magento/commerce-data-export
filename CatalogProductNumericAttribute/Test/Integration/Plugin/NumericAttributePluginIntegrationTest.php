<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration\Plugin;

use Magento\Eav\Helper\Data as EavHelperData;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that the DI plugins for CatalogProductNumericAttribute are correctly wired
 * and that the "numeric" frontend_input type is handled throughout the system.
 *
 * @magentoAppArea adminhtml
 */
class NumericAttributePluginIntegrationTest extends TestCase
{
    /** @var EavAttribute */
    private EavAttribute $attribute;

    /** @var EavHelperData */
    private EavHelperData $eavHelperData;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->attribute = $objectManager->create(EavAttribute::class);
        $this->eavHelperData = $objectManager->get(EavHelperData::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetBackendTypeByInputReturnsDecimalForNumeric(): void
    {
        $backendType = $this->attribute->getBackendTypeByInput('numeric');
        $this->assertSame(
            'decimal',
            $backendType,
            'The AttributePlugin must map frontend_input "numeric" to backend_type "decimal"'
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetBackendTypeByInputDoesNotAffectStandardTypes(): void
    {
        $this->assertSame('varchar', $this->attribute->getBackendTypeByInput('text'));
        $this->assertSame('int', $this->attribute->getBackendTypeByInput('select'));
        $this->assertSame('decimal', $this->attribute->getBackendTypeByInput('price'));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetDefaultValueByInputReturnsDefaultValueTextForNumeric(): void
    {
        $defaultValueField = $this->attribute->getDefaultValueByInput('numeric');
        $this->assertSame(
            'default_value_text',
            $defaultValueField,
            'The AttributePlugin must map frontend_input "numeric" to default_value_field "default_value_text"'
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetInputTypesValidatorDataContainsNumeric(): void
    {
        $validatorData = $this->eavHelperData->getInputTypesValidatorData();
        $this->assertIsArray($validatorData);
        $this->assertArrayHasKey(
            'numeric',
            $validatorData,
            'The DataPlugin must add "numeric" to the EAV input-type validator data'
        );
        $this->assertSame('numeric', $validatorData['numeric']);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetInputTypesValidatorDataPreservesStandardTypes(): void
    {
        $validatorData = $this->eavHelperData->getInputTypesValidatorData();
        $this->assertArrayHasKey('text', $validatorData);
        $this->assertArrayHasKey('numeric', $validatorData);
    }
}
