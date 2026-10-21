<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Test\Integration\Unit\Observer;

use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewSummaryAttributeRegistry;
use AdobeCommerce\ProductReviewSummarySync\Model\ReviewSummaryResync;
use AdobeCommerce\ProductReviewSummarySync\Observer\ResyncOnReviewActiveChange;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResyncOnReviewActiveChangeTest extends TestCase
{
    private const SAVE_EVENT = 'config_data_save_after';
    private const DELETE_EVENT = 'config_data_delete_after';

    /**
     * @var ReviewSummaryResync&MockObject
     */
    private ReviewSummaryResync $resync;

    /**
     * @var ReviewSummaryAttributeRegistry&MockObject
     */
    private ReviewSummaryAttributeRegistry $registry;

    /**
     * @var ResyncOnReviewActiveChange
     */
    private ResyncOnReviewActiveChange $observer;

    protected function setUp(): void
    {
        $this->resync = $this->createMock(ReviewSummaryResync::class);
        $this->registry = $this->createMock(ReviewSummaryAttributeRegistry::class);
        $this->observer = new ResyncOnReviewActiveChange($this->resync, $this->registry);
    }

    public function testEnableReconciles(): void
    {
        $this->registry->method('isRegistered')->willReturn(false);
        $this->resync->expects($this->once())->method('reconcile')->with();

        $this->observer->execute($this->createObserver(self::SAVE_EVENT, 'catalog/review/active', '1', true));
    }

    public function testDisableReconcilesWhenAttributesAreRegistered(): void
    {
        $this->registry->method('isRegistered')->willReturn(true);
        $this->resync->expects($this->once())->method('reconcile');

        $this->observer->execute($this->createObserver(self::SAVE_EVENT, 'catalog/review/active', '0', true));
    }

    public function testDisableIsSkippedWhenAttributesAreNotRegistered(): void
    {
        $this->registry->method('isRegistered')->willReturn(false);
        $this->resync->expects($this->never())->method('reconcile');

        $this->observer->execute($this->createObserver(self::SAVE_EVENT, 'catalog/review/active', '0', true));
    }

    public function testUnchangedValueIsSkipped(): void
    {
        $this->resync->expects($this->never())->method('reconcile');

        $this->observer->execute($this->createObserver(self::SAVE_EVENT, 'catalog/review/active', '1', false));
    }

    public function testOtherPathIsSkipped(): void
    {
        $this->resync->expects($this->never())->method('reconcile');

        $this->observer->execute($this->createObserver(self::SAVE_EVENT, 'catalog/review/allow_guest', '1', true));
    }

    public function testDeleteReconciles(): void
    {
        $this->registry->method('isRegistered')->willReturn(false);
        $this->resync->expects($this->once())->method('reconcile');

        $this->observer->execute($this->createObserver(self::DELETE_EVENT, 'catalog/review/active', '0', false));
    }

    public function testMissingConfigIsSkipped(): void
    {
        $this->resync->expects($this->never())->method('reconcile');

        $this->observer->execute(new Observer(['event' => new Event(['name' => self::SAVE_EVENT])]));
    }

    /**
     * @param string $eventName
     * @param string $path
     * @param string $value
     * @param bool $isValueChanged
     * @return Observer
     */
    private function createObserver(string $eventName, string $path, string $value, bool $isValueChanged): Observer
    {
        $config = $this->getMockBuilder(Value::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isValueChanged'])
            ->getMock();
        $config->setData(['path' => $path, 'value' => $value]);
        $config->method('isValueChanged')->willReturn($isValueChanged);

        return new Observer(['event' => new Event(['name' => $eventName, 'config_data' => $config])]);
    }
}
