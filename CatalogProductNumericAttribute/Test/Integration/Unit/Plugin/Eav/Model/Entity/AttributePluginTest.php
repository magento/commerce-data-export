<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration\Unit\Plugin\Eav\Model\Entity;

use AdobeCommerce\CatalogProductNumericAttribute\Plugin\Eav\Model\Entity\AttributePlugin;
use Magento\Eav\Model\Entity\Attribute;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributePluginTest extends TestCase
{
    /** @var AttributePlugin */
    private AttributePlugin $plugin;

    /** @var Attribute&MockObject */
    private Attribute $subjectMock;

    protected function setUp(): void
    {
        $this->plugin = new AttributePlugin();
        $this->subjectMock = $this->createMock(Attribute::class);
    }

    public function testAfterGetBackendTypeByInputReturnsDecimalForNumeric(): void
    {
        $result = $this->plugin->afterGetBackendTypeByInput($this->subjectMock, null, 'numeric');
        $this->assertSame('decimal', $result);
    }

    public function testAfterGetBackendTypeByInputPassesThroughForOtherTypes(): void
    {
        $this->assertSame('varchar', $this->plugin->afterGetBackendTypeByInput($this->subjectMock, 'varchar', 'text'));
        $this->assertSame('int', $this->plugin->afterGetBackendTypeByInput($this->subjectMock, 'int', 'select'));
        $this->assertNull($this->plugin->afterGetBackendTypeByInput($this->subjectMock, null, 'price'));
    }

    public function testAfterGetBackendTypeByInputOverridesExistingResultForNumeric(): void
    {
        // Numeric always wins even if core returned something
        $result = $this->plugin->afterGetBackendTypeByInput($this->subjectMock, 'varchar', 'numeric');
        $this->assertSame('decimal', $result);
    }

    public function testAfterGetDefaultValueByInputReturnsDefaultValueTextForNumeric(): void
    {
        $result = $this->plugin->afterGetDefaultValueByInput($this->subjectMock, null, 'numeric');
        $this->assertSame('default_value_text', $result);
    }

    public function testAfterGetDefaultValueByInputPassesThroughForOtherTypes(): void
    {
        $this->assertSame(
            'default_value_text',
            $this->plugin->afterGetDefaultValueByInput($this->subjectMock, 'default_value_text', 'text')
        );
        $this->assertSame(
            'default_value_yesno',
            $this->plugin->afterGetDefaultValueByInput($this->subjectMock, 'default_value_yesno', 'boolean')
        );
        $this->assertNull($this->plugin->afterGetDefaultValueByInput($this->subjectMock, null, 'price'));
    }

    public function testAfterGetDefaultValueByInputOverridesExistingResultForNumeric(): void
    {
        $result = $this->plugin->afterGetDefaultValueByInput($this->subjectMock, 'default_value_yesno', 'numeric');
        $this->assertSame('default_value_text', $result);
    }
}
