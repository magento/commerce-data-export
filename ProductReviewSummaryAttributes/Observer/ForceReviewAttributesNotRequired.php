<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummaryAttributes\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewAttributeContract;

/**
 * Keeps the review summary attributes not required on every save path (Admin, REST, repository).
 *
 * The attributes have no value on the product edit form, so marking them as required would block every
 * product save in the attribute set.
 */
class ForceReviewAttributesNotRequired implements ObserverInterface
{
    /**
     * @param ReviewAttributeContract $contract
     */
    public function __construct(
        private readonly ReviewAttributeContract $contract
    ) {
    }

    /**
     * Reset "is_required" before the review summary attribute is saved.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $attribute = $observer->getEvent()->getData('attribute');
        if (!$attribute instanceof DataObject || !$this->contract->isReviewSummaryAttribute($attribute)) {
            return;
        }

        $attribute->setIsRequired(0);
    }
}
