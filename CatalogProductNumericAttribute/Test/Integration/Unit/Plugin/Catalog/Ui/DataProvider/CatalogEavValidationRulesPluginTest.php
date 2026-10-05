<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration\Unit\Plugin\Catalog\Ui\DataProvider;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Ui\DataProvider\CatalogEavValidationRules;
use AdobeCommerce\CatalogProductNumericAttribute\Plugin\Catalog\Ui\DataProvider\CatalogEavValidationRulesPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogEavValidationRulesPluginTest extends TestCase
{
    /** @var CatalogEavValidationRulesPlugin */
    private CatalogEavValidationRulesPlugin $plugin;

    /** @var CatalogEavValidationRules&MockObject */
    private CatalogEavValidationRules $subjectMock;

    /** @var ProductAttributeInterface&MockObject */
    private ProductAttributeInterface $attributeMock;

    protected function setUp(): void
    {
        $this->plugin = new CatalogEavValidationRulesPlugin();
        $this->subjectMock = $this->createMock(CatalogEavValidationRules::class);
        $this->attributeMock = $this->createMock(ProductAttributeInterface::class);
    }

    public function testAfterBuildAddsValidateNumberForNumericInput(): void
    {
        $this->attributeMock->method('getFrontendInput')->willReturn('numeric');

        $result = $this->plugin->afterBuild($this->subjectMock, [], $this->attributeMock);

        $this->assertArrayHasKey('validate-number', $result);
        $this->assertTrue($result['validate-number']);
    }

    public function testAfterBuildPreservesExistingRulesForNumericInput(): void
    {
        $this->attributeMock->method('getFrontendInput')->willReturn('numeric');

        $existing = ['required-entry' => true, 'min_text_length' => 1];
        $result = $this->plugin->afterBuild($this->subjectMock, $existing, $this->attributeMock);

        $this->assertArrayHasKey('validate-number', $result);
        $this->assertTrue($result['validate-number']);
        $this->assertArrayHasKey('required-entry', $result);
        $this->assertArrayHasKey('min_text_length', $result);
    }

    public function testAfterBuildDoesNotAddValidateNumberForTextInput(): void
    {
        $this->attributeMock->method('getFrontendInput')->willReturn('text');

        $result = $this->plugin->afterBuild($this->subjectMock, [], $this->attributeMock);

        $this->assertArrayNotHasKey('validate-number', $result);
    }

    public function testAfterBuildDoesNotAddValidateNumberForSelectInput(): void
    {
        $this->attributeMock->method('getFrontendInput')->willReturn('select');

        $result = $this->plugin->afterBuild($this->subjectMock, ['required-entry' => true], $this->attributeMock);

        $this->assertArrayNotHasKey('validate-number', $result);
    }

    public function testAfterBuildDoesNotAddValidateNumberForPriceInput(): void
    {
        $this->attributeMock->method('getFrontendInput')->willReturn('price');

        $result = $this->plugin->afterBuild($this->subjectMock, [], $this->attributeMock);

        $this->assertArrayNotHasKey('validate-number', $result);
    }

    public function testAfterBuildPassesThroughEmptyResultForNonNumericInput(): void
    {
        $this->attributeMock->method('getFrontendInput')->willReturn('boolean');

        $result = $this->plugin->afterBuild($this->subjectMock, [], $this->attributeMock);

        $this->assertEmpty($result);
    }
}
