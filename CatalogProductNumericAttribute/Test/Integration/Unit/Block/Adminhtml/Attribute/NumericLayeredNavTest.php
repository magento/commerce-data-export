<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration\Unit\Block\Adminhtml\Attribute;

use AdobeCommerce\CatalogProductNumericAttribute\Block\Adminhtml\Attribute\NumericLayeredNav;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NumericLayeredNavTest extends TestCase
{
    /** @var Registry&MockObject */
    private Registry $registryMock;

    /** @var NumericLayeredNav */
    private NumericLayeredNav $block;

    protected function setUp(): void
    {
        $this->registryMock = $this->createMock(Registry::class);

        $this->block = $this->getMockBuilder(NumericLayeredNav::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $reflection = new \ReflectionProperty(NumericLayeredNav::class, 'registry');
        $reflection->setValue($this->block, $this->registryMock);
    }

    public function testGetIsFilterableReturnsValueFromRegisteredAttribute(): void
    {
        $attribute = new DataObject(['is_filterable' => 2]);
        $this->registryMock->method('registry')
            ->with('entity_attribute')
            ->willReturn($attribute);

        $this->assertSame(2, $this->block->getIsFilterable());
    }

    public function testGetIsFilterableReturnsZeroWhenNoAttributeInRegistry(): void
    {
        $this->registryMock->method('registry')
            ->with('entity_attribute')
            ->willReturn(null);

        $this->assertSame(0, $this->block->getIsFilterable());
    }

    public function testGetIsFilterableCastsValueToInt(): void
    {
        $attribute = new DataObject(['is_filterable' => '1']);
        $this->registryMock->method('registry')
            ->with('entity_attribute')
            ->willReturn($attribute);

        $result = $this->block->getIsFilterable();
        $this->assertIsInt($result);
        $this->assertSame(1, $result);
    }

    public function testGetIsFilterableInSearchReturnsValueFromRegisteredAttribute(): void
    {
        $attribute = new DataObject(['is_filterable_in_search' => 1]);
        $this->registryMock->method('registry')
            ->with('entity_attribute')
            ->willReturn($attribute);

        $this->assertSame(1, $this->block->getIsFilterableInSearch());
    }

    public function testGetIsFilterableInSearchReturnsZeroWhenNoAttributeInRegistry(): void
    {
        $this->registryMock->method('registry')
            ->with('entity_attribute')
            ->willReturn(null);

        $this->assertSame(0, $this->block->getIsFilterableInSearch());
    }

    public function testGetIsFilterableInSearchCastsValueToInt(): void
    {
        $attribute = new DataObject(['is_filterable_in_search' => '0']);
        $this->registryMock->method('registry')
            ->with('entity_attribute')
            ->willReturn($attribute);

        $result = $this->block->getIsFilterableInSearch();
        $this->assertIsInt($result);
        $this->assertSame(0, $result);
    }

    public function testGetIsFilterableReturnsZeroWhenAttributeHasNoData(): void
    {
        $attribute = new DataObject([]);
        $this->registryMock->method('registry')
            ->with('entity_attribute')
            ->willReturn($attribute);

        $this->assertSame(0, $this->block->getIsFilterable());
    }

    public function testGetIsFilterableInSearchReturnsZeroWhenAttributeHasNoData(): void
    {
        $attribute = new DataObject([]);
        $this->registryMock->method('registry')
            ->with('entity_attribute')
            ->willReturn($attribute);

        $this->assertSame(0, $this->block->getIsFilterableInSearch());
    }
}
