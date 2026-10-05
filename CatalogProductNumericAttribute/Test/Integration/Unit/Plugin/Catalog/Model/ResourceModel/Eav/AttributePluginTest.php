<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration\Unit\Plugin\Catalog\Model\ResourceModel\Eav;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use AdobeCommerce\CatalogProductNumericAttribute\Plugin\Catalog\Model\ResourceModel\Eav\AttributePlugin;
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

    public function testAfterIsAllowedForRuleConditionReturnsTrueForVisibleNumericAttribute(): void
    {
        $this->subjectMock->method('getIsVisible')->willReturn(true);
        $this->subjectMock->method('getFrontendInput')->willReturn('numeric');

        $result = $this->plugin->afterIsAllowedForRuleCondition($this->subjectMock, false);
        $this->assertTrue($result);
    }

    public function testAfterIsAllowedForRuleConditionPassesTrueResultThrough(): void
    {
        // If the core already returned true, do not change it
        $this->subjectMock->method('getIsVisible')->willReturn(true);
        $this->subjectMock->method('getFrontendInput')->willReturn('numeric');

        $result = $this->plugin->afterIsAllowedForRuleCondition($this->subjectMock, true);
        $this->assertTrue($result);
    }

    public function testAfterIsAllowedForRuleConditionReturnsFalseWhenNotVisible(): void
    {
        $this->subjectMock->method('getIsVisible')->willReturn(false);
        $this->subjectMock->method('getFrontendInput')->willReturn('numeric');

        $result = $this->plugin->afterIsAllowedForRuleCondition($this->subjectMock, false);
        $this->assertFalse($result);
    }

    public function testAfterIsAllowedForRuleConditionReturnsFalseForNonNumericType(): void
    {
        $this->subjectMock->method('getIsVisible')->willReturn(true);
        $this->subjectMock->method('getFrontendInput')->willReturn('text');

        $result = $this->plugin->afterIsAllowedForRuleCondition($this->subjectMock, false);
        $this->assertFalse($result);
    }

    public function testAfterIsAllowedForRuleConditionReturnsFalseForSelectType(): void
    {
        $this->subjectMock->method('getIsVisible')->willReturn(true);
        $this->subjectMock->method('getFrontendInput')->willReturn('select');

        $result = $this->plugin->afterIsAllowedForRuleCondition($this->subjectMock, false);
        $this->assertFalse($result);
    }

    public function testAfterIsAllowedForRuleConditionReturnsTrueFromCoreForNonNumericType(): void
    {
        // Ensure core's true result is preserved for non-numeric types too
        $this->subjectMock->method('getIsVisible')->willReturn(true);
        $this->subjectMock->method('getFrontendInput')->willReturn('price');

        $result = $this->plugin->afterIsAllowedForRuleCondition($this->subjectMock, true);
        $this->assertTrue($result);
    }
}
