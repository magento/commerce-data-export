<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration\Unit\Plugin\Eav\Helper;

use AdobeCommerce\CatalogProductNumericAttribute\Plugin\Eav\Helper\DataPlugin;
use Magento\Eav\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataPluginTest extends TestCase
{
    /** @var DataPlugin */
    private DataPlugin $plugin;

    /** @var Data&MockObject */
    private Data $subjectMock;

    protected function setUp(): void
    {
        $this->plugin = new DataPlugin();
        $this->subjectMock = $this->createMock(Data::class);
    }

    public function testAfterGetInputTypesValidatorDataAddsNumericToEmptyArray(): void
    {
        $result = $this->plugin->afterGetInputTypesValidatorData($this->subjectMock, []);
        $this->assertArrayHasKey('numeric', $result);
        $this->assertSame('numeric', $result['numeric']);
    }

    public function testAfterGetInputTypesValidatorDataAddsNumericToExistingTypes(): void
    {
        $existing = ['text' => 'text', 'select' => 'select'];
        $result = $this->plugin->afterGetInputTypesValidatorData($this->subjectMock, $existing);

        $this->assertArrayHasKey('numeric', $result);
        $this->assertSame('numeric', $result['numeric']);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('select', $result);
    }

    public function testAfterGetInputTypesValidatorDataDoesNotDuplicateNumeric(): void
    {
        $existing = ['numeric' => 'numeric', 'text' => 'text'];
        $result = $this->plugin->afterGetInputTypesValidatorData($this->subjectMock, $existing);

        $this->assertCount(2, $result);
        $this->assertSame('numeric', $result['numeric']);
    }

    public function testAfterGetInputTypesValidatorDataHandlesNullResult(): void
    {
        $result = $this->plugin->afterGetInputTypesValidatorData($this->subjectMock, null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('numeric', $result);
        $this->assertSame('numeric', $result['numeric']);
    }

    public function testAfterGetInputTypesValidatorDataPreservesExistingValues(): void
    {
        $existing = ['text' => 'text', 'price' => 'price', 'boolean' => 'boolean'];
        $result = $this->plugin->afterGetInputTypesValidatorData($this->subjectMock, $existing);

        foreach ($existing as $key => $value) {
            $this->assertArrayHasKey($key, $result);
            $this->assertSame($value, $result[$key]);
        }
    }
}
