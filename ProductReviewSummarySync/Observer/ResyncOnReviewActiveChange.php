<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Observer;

use AdobeCommerce\ProductReviewSummaryAttributes\Model\ReviewSummaryAttributeRegistry;
use AdobeCommerce\ProductReviewSummarySync\Model\ReviewSummaryResync;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Review\Model\Review\Config as ReviewConfig;

/**
 * Re-export reviewed products when "Enable Product Reviews" changes in any scope.
 *
 * Enabling adds the review summary values to the products feed, disabling removes them.
 * Listens to config_data_save_after and config_data_delete_after (scope value removed, so the inherited
 * value applies), which covers Admin and bin/magento config:set (including --lock-env and --lock-config).
 * Values deployed through config.php or env.php and imported with app:config:import are not detected: the backend
 * model is created with the value already in the config files, so isValueChanged() is always false.
 */
class ResyncOnReviewActiveChange implements ObserverInterface
{
    private const DELETE_EVENT = 'config_data_delete_after';

    /**
     * @param ReviewSummaryResync $reviewSummaryResync
     * @param ReviewSummaryAttributeRegistry $attributeRegistry
     */
    public function __construct(
        private readonly ReviewSummaryResync $reviewSummaryResync,
        private readonly ReviewSummaryAttributeRegistry $attributeRegistry
    ) {
    }

    /**
     * Schedule reviewed products for re-export on catalog/review/active change.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $config = $observer->getEvent()->getData('config_data');
        if (!$config instanceof Value || $config->getPath() !== ReviewConfig::XML_PATH_REVIEW_ACTIVE) {
            return;
        }
        $isDelete = $observer->getEvent()->getName() === self::DELETE_EVENT;
        if (!$isDelete && !$config->isValueChanged()) {
            return;
        }
        // Reviews were disabled and no values were ever exported: nothing to remove from the feed
        if (!$isDelete && !(bool)$config->getValue() && !$this->attributeRegistry->isRegistered()) {
            return;
        }

        $this->reviewSummaryResync->reconcile();
    }
}
