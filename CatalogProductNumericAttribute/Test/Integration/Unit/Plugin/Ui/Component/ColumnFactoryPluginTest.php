<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration\Unit\Plugin\Ui\Component;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Ui\Component\ColumnFactory;
use AdobeCommerce\CatalogProductNumericAttribute\Plugin\Ui\Component\ColumnFactoryPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ColumnFactoryPluginTest extends TestCase
{
    /** @var ColumnFactoryPlugin */
    private ColumnFactoryPlugin $plugin;

    /** @var ColumnFactory&MockObject */
    private ColumnFactory $columnFactoryMock;

    protected function setUp(): void
    {
        $this->plugin = new ColumnFactoryPlugin();
        $this->columnFactoryMock = $this->createMock(ColumnFactory::class);
    }

    /**
     * @dataProvider provideBeforeCreateScenarios
     */
    public function testBeforeCreate(
        string $frontendInput,
        bool $filterableInGrid,
        array $inputConfig,
        array $expectedConfig
    ): void {
        $attribute = $this->createMock(ProductAttributeInterface::class);
        $attribute->method('getFrontendInput')->willReturn($frontendInput);
        $attribute->method('getIsFilterableInGrid')->willReturn($filterableInGrid);

        $result = $this->plugin->beforeCreate($this->columnFactoryMock, $attribute, new \stdClass(), $inputConfig);

        $this->assertSame($expectedConfig, $result[2]);
    }

    /**
     * @return array
     */
    public static function provideBeforeCreateScenarios(): array
    {
        return [
            'numeric filterable in grid sets textRange' => [
                'frontendInput' => 'numeric',
                'filterableInGrid' => true,
                'inputConfig' => ['filter' => 'text', 'sortOrder' => 120],
                'expectedConfig' => ['filter' => 'textRange', 'sortOrder' => 120],
            ],
            'numeric not filterable in grid keeps config unchanged' => [
                'frontendInput' => 'numeric',
                'filterableInGrid' => false,
                'inputConfig' => ['sortOrder' => 5],
                'expectedConfig' => ['sortOrder' => 5],
            ],
            'non-numeric filterable in grid keeps config unchanged' => [
                'frontendInput' => 'text',
                'filterableInGrid' => true,
                'inputConfig' => ['filter' => 'text'],
                'expectedConfig' => ['filter' => 'text'],
            ],
        ];
    }
}
