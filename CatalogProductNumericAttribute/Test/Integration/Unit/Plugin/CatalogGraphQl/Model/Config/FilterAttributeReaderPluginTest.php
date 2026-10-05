<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration\Unit\Plugin\CatalogGraphQl\Model\Config;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\CatalogGraphQl\Model\Config\FilterAttributeReader;
use AdobeCommerce\CatalogProductNumericAttribute\Plugin\CatalogGraphQl\Model\Config\FilterAttributeReaderPlugin;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FilterAttributeReaderPluginTest extends TestCase
{
    /** @var CollectionFactory&MockObject */
    private CollectionFactory $collectionFactoryMock;

    /** @var Collection&MockObject */
    private Collection $collectionMock;

    /** @var FilterAttributeReader&MockObject */
    private FilterAttributeReader $subjectMock;

    /** @var FilterAttributeReaderPlugin */
    private FilterAttributeReaderPlugin $plugin;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectMock = $this->createMock(FilterAttributeReader::class);

        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);

        $this->plugin = new FilterAttributeReaderPlugin($this->collectionFactoryMock);
    }

    public function testAfterReadRemapsNumericAttributeToFilterRangeTypeInput(): void
    {
        $attributeMock = $this->createAttributeMock('temperature_range');

        $this->collectionMock->method('getItems')->willReturn([$attributeMock]);

        $input = [
            'ProductFilterInput' => [
                'fields' => [
                    'temperature_range' => ['type' => 'FilterMatchTypeInput'],
                    'name' => ['type' => 'FilterMatchTypeInput'],
                ],
            ],
        ];

        $result = $this->plugin->afterRead($this->subjectMock, $input);

        $this->assertSame(
            'FilterRangeTypeInput',
            $result['ProductFilterInput']['fields']['temperature_range']['type']
        );
        $this->assertSame(
            'FilterMatchTypeInput',
            $result['ProductFilterInput']['fields']['name']['type'],
            'Non-numeric attributes must not be changed'
        );
    }

    public function testAfterReadReturnsOriginalResultWhenNoNumericAttributes(): void
    {
        $this->collectionMock->method('getItems')->willReturn([]);

        $input = [
            'ProductFilterInput' => [
                'fields' => [
                    'name' => ['type' => 'FilterMatchTypeInput'],
                ],
            ],
        ];

        $result = $this->plugin->afterRead($this->subjectMock, $input);

        $this->assertSame($input, $result);
    }

    public function testAfterReadHandlesTypeConfigWithoutFieldsKey(): void
    {
        $attributeMock = $this->createAttributeMock('temperature_range');
        $this->collectionMock->method('getItems')->willReturn([$attributeMock]);

        $input = [
            'SomeOtherType' => ['name' => 'SomeOtherType'],
        ];

        $result = $this->plugin->afterRead($this->subjectMock, $input);

        $this->assertSame($input, $result);
    }

    public function testAfterReadDoesNotChangeAttributeNotPresentInFields(): void
    {
        $attributeMock = $this->createAttributeMock('temperature_range');
        $this->collectionMock->method('getItems')->willReturn([$attributeMock]);

        $input = [
            'ProductFilterInput' => [
                'fields' => [
                    'price' => ['type' => 'FilterRangeTypeInput'],
                ],
            ],
        ];

        $result = $this->plugin->afterRead($this->subjectMock, $input);

        $this->assertSame('FilterRangeTypeInput', $result['ProductFilterInput']['fields']['price']['type']);
        $this->assertArrayNotHasKey('temperature_range', $result['ProductFilterInput']['fields']);
    }

    public function testAfterReadRemapsMultipleNumericAttributesAcrossTypeConfigs(): void
    {
        $attr1 = $this->createAttributeMock('min_temp');
        $attr2 = $this->createAttributeMock('max_temp');
        $this->collectionMock->method('getItems')->willReturn([$attr1, $attr2]);

        $input = [
            'ProductFilterInput' => [
                'fields' => [
                    'min_temp' => ['type' => 'FilterMatchTypeInput'],
                    'max_temp' => ['type' => 'FilterMatchTypeInput'],
                    'name' => ['type' => 'FilterMatchTypeInput'],
                ],
            ],
        ];

        $result = $this->plugin->afterRead($this->subjectMock, $input);

        $this->assertSame('FilterRangeTypeInput', $result['ProductFilterInput']['fields']['min_temp']['type']);
        $this->assertSame('FilterRangeTypeInput', $result['ProductFilterInput']['fields']['max_temp']['type']);
        $this->assertSame('FilterMatchTypeInput', $result['ProductFilterInput']['fields']['name']['type']);
    }

    /**
     * @param string $attributeCode
     * @return AbstractAttribute&MockObject
     */
    private function createAttributeMock(string $attributeCode): AbstractAttribute
    {
        $mock = $this->getMockBuilder(AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->method('getAttributeCode')->willReturn($attributeCode);
        return $mock;
    }
}
