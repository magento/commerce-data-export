<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Test\Integration\Unit\Plugin\Index;

use Magento\CatalogDataExporter\Plugin\Index\InvalidateOnConfigChange;
use Magento\Config\Model\Config;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Service\IndexInvalidationManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvalidateOnConfigChangeTest extends TestCase
{
    private ScopeConfigInterface&MockObject $scopeConfig;
    private IndexInvalidationManager&MockObject $invalidationManager;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->invalidationManager = $this->createMock(IndexInvalidationManager::class);
    }

    /**
     * @param array<string,string> $configPathToEvent path => event name
     */
    private function createPlugin(array $configPathToEvent): InvalidateOnConfigChange
    {
        return new InvalidateOnConfigChange(
            $this->scopeConfig,
            $this->createMock(CommerceDataExportLoggerInterface::class),
            $configPathToEvent,
            $this->invalidationManager
        );
    }

    private function buildConfigMock(string $section, array $groups): Config&MockObject
    {
        $mock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $mock->method('getData')->willReturnCallback(fn($key) => match($key) {
            'section' => $section,
            'groups' => $groups,
            default => null,
        });
        $mock->setData('groups', $groups);
        return $mock;
    }

    public function testFiresPathSpecificEventWhenConfigChanges(): void
    {
        $configModel = $this->buildConfigMock('currency', [
            'options' => ['fields' => ['base' => ['value' => 'CAD']]]
        ]);

        $this->scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->with('currency/options/base')
            ->willReturnOnConsecutiveCalls('USD', 'CAD');

        $this->invalidationManager->expects($this->once())
            ->method('invalidate')
            ->with('currency_changed');

        $this->createPlugin(['currency/options/base' => 'currency_changed'])
            ->aroundSave($configModel, fn() => null);
    }

    public function testFiresSingleEventOnceWhenMultiplePathsMapToIt(): void
    {
        $configModel = $this->buildConfigMock('cataloginventory', [
            'options' => [
                'fields' => [
                    'show_out_of_stock' => ['value' => '1'],
                    'stock_threshold_qty' => ['value' => '5'],
                ]
            ]
        ]);

        // both paths change, both map to inventory_config_changed
        $this->scopeConfig->method('getValue')
            ->willReturnOnConsecutiveCalls('0', '0', '1', '1');

        $this->invalidationManager->expects($this->once())
            ->method('invalidate')
            ->with('inventory_config_changed');

        $this->createPlugin([
            'cataloginventory/options/show_out_of_stock' => 'inventory_config_changed',
            'cataloginventory/options/stock_threshold_qty' => 'inventory_config_changed',
        ])->aroundSave($configModel, fn() => null);
    }

    public function testFiresDistinctEventsForDifferentPathGroups(): void
    {
        $configModel = $this->buildConfigMock('cataloginventory', [
            'options' => [
                'fields' => [
                    'show_out_of_stock' => ['value' => '1'],
                    'stock_threshold_qty' => ['value' => '5'],
                ]
            ]
        ]);

        $this->scopeConfig->method('getValue')
            ->willReturnOnConsecutiveCalls('0', '0', '1', '1');

        $this->invalidationManager->expects($this->exactly(2))
            ->method('invalidate')
            ->willReturnCallback(function (string $event) {
                static $fired = [];
                $fired[] = $event;
                if (count($fired) === 2) {
                    $this->assertEqualsCanonicalizing(
                        ['event_a', 'event_b'],
                        $fired
                    );
                }
            });

        $this->createPlugin([
            'cataloginventory/options/show_out_of_stock' => 'event_a',
            'cataloginventory/options/stock_threshold_qty' => 'event_b',
        ])->aroundSave($configModel, fn() => null);
    }

    public function testDoesNotFireEventWhenPathValueUnchanged(): void
    {
        $configModel = $this->buildConfigMock('currency', [
            'options' => ['fields' => ['base' => ['value' => 'USD']]]
        ]);

        $this->scopeConfig->method('getValue')->willReturn('USD');

        $this->invalidationManager->expects($this->never())->method('invalidate');

        $this->createPlugin(['currency/options/base' => 'currency_changed'])
            ->aroundSave($configModel, fn() => null);
    }

    public function testDoesNotFireEventWhenUnrelatedSectionSaved(): void
    {
        $configModel = $this->buildConfigMock('web', []);

        $this->scopeConfig->expects($this->never())->method('getValue');
        $this->invalidationManager->expects($this->never())->method('invalidate');

        $this->createPlugin(['currency/options/base' => 'currency_changed'])
            ->aroundSave($configModel, fn() => null);
    }
}
