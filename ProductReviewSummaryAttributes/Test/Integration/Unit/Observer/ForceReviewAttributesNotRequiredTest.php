<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Test\Integration\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract;
use AdobeCommerce\ProductReviewSummaryAttributes\Observer\ForceReviewAttributesNotRequired;
use PHPUnit\Framework\TestCase;

class ForceReviewAttributesNotRequiredTest extends TestCase
{
    public function testReviewAttributeIsForcedNotRequired(): void
    {
        $attribute = new DataObject([
            'attribute_code' => 'ac_review_count',
            'is_user_defined' => '0',
            'frontend_input' => 'numeric',
            'is_required' => 1,
        ]);

        $this->observer()->execute($this->createObserver($attribute));

        $this->assertSame(0, $attribute->getIsRequired());
    }

    public function testOtherAttributeIsNotChanged(): void
    {
        $attribute = new DataObject(['attribute_code' => 'color', 'is_required' => 1]);

        $this->observer()->execute($this->createObserver($attribute));

        $this->assertSame(1, $attribute->getIsRequired());
    }

    public function testMerchantAttributeWithSameCodeIsNotChanged(): void
    {
        $attribute = new DataObject([
            'attribute_code' => 'ac_review_count',
            'is_user_defined' => 1,
            'frontend_input' => 'numeric',
            'is_required' => 1,
        ]);

        $this->observer()->execute($this->createObserver($attribute));

        $this->assertSame(1, $attribute->getIsRequired());
    }

    public function testSystemAttributeWithSameCodeAndOtherInputIsNotChanged(): void
    {
        $attribute = new DataObject([
            'attribute_code' => 'ac_review_count',
            'is_user_defined' => '0',
            'frontend_input' => 'text',
            'is_required' => 1,
        ]);

        $this->observer()->execute($this->createObserver($attribute));

        $this->assertSame(1, $attribute->getIsRequired());
    }

    public function testMissingAttributeIsIgnored(): void
    {
        $this->observer()->execute($this->createObserver(null));

        $this->addToAssertionCount(1);
    }

    /**
     * @return ForceReviewAttributesNotRequired
     */
    private function observer(): ForceReviewAttributesNotRequired
    {
        return new ForceReviewAttributesNotRequired(new ReviewAttributeContract());
    }

    /**
     * @param DataObject|null $attribute
     * @return Observer
     */
    private function createObserver(?DataObject $attribute): Observer
    {
        return new Observer(['event' => new Event(['attribute' => $attribute])]);
    }
}
