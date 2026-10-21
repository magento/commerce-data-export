<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Test\Integration\Unit\Observer;

use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\Form\Element\Select;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract;
use AdobeCommerce\ProductReviewSummaryAttributes\Observer\ConfigureReviewAttributeEditForm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigureReviewAttributeEditFormTest extends TestCase
{
    /** @var Registry&MockObject */
    private Registry $registry;

    /** @var Form&MockObject */
    private Form $form;

    /** @var Fieldset&MockObject */
    private Fieldset $fieldset;

    /** @var Observer */
    private Observer $observer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->form = $this->createMock(Form::class);
        $this->fieldset = $this->createMock(Fieldset::class);
        $this->observer = new Observer(['event' => new Event(['form' => $this->form])]);
    }

    public function testNoticeIsAddedAndValuesRequiredIsLockedForReviewAttribute(): void
    {
        $isRequired = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->registry->method('registry')
            ->with('entity_attribute')
            ->willReturn(new DataObject([
                'attribute_code' => 'ac_rating_summary',
                'is_user_defined' => '0',
                'frontend_input' => 'numeric',
            ]));
        $this->form->method('getElement')->willReturnMap([
            ['base_fieldset', $this->fieldset],
            ['is_required', $isRequired],
        ]);
        $this->fieldset->expects($this->once())
            ->method('addField')
            ->with('product_review_summary_notice', 'note', $this->arrayHasKey('text'), '^');

        $this->editForm()->execute($this->observer);

        $this->assertTrue($isRequired->getDisabled());
    }

    public function testNoticeIsNotAddedForOtherAttributes(): void
    {
        $this->registry->method('registry')->willReturn(new DataObject(['attribute_code' => 'color']));
        $this->form->expects($this->never())->method('getElement');

        $this->editForm()->execute($this->observer);
    }

    public function testNoticeIsNotAddedForMerchantAttributeWithSameCode(): void
    {
        $this->registry->method('registry')
            ->willReturn(new DataObject([
                'attribute_code' => 'ac_rating_summary',
                'is_user_defined' => '1',
                'frontend_input' => 'numeric',
            ]));
        $this->form->expects($this->never())->method('getElement');

        $this->editForm()->execute($this->observer);
    }

    public function testNoticeIsNotAddedWithoutAttribute(): void
    {
        $this->registry->method('registry')->willReturn(null);
        $this->form->expects($this->never())->method('getElement');

        $this->editForm()->execute($this->observer);
    }

    /**
     * @return ConfigureReviewAttributeEditForm
     */
    private function editForm(): ConfigureReviewAttributeEditForm
    {
        return new ConfigureReviewAttributeEditForm($this->registry, new ReviewAttributeContract());
    }
}
