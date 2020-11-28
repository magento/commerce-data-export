<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Event;

interface ProductPriceEventInterface
{
    /**
     * Price event type constants
     */
    public const EVENT_PRICE_CHANGED = 'price_changed';
    public const EVENT_PRICE_DELETED = 'price_deleted';
    public const EVENT_TIER_PRICE_CHANGED = 'tier_price_changed';
    public const EVENT_TIER_PRICE_DELETED = 'tier_price_deleted';
    public const EVENT_CUSTOM_OPTION_PRICE_CHANGED = 'custom_option_price_changed';
    public const EVENT_CUSTOM_OPTION_PRICE_DELETED = 'custom_option_price_deleted';
    public const EVENT_DOWNLOADABLE_LINK_PRICE_CHANGED = 'downloadable_link_price_changed';
    public const EVENT_DOWNLOADABLE_LINK_PRICE_DELETED = 'downloadable_link_price_deleted';
    public const EVENT_VARIATION_CHANGED = 'variation_changed';
    public const EVENT_VARIATION_DELETED = 'variation_deleted';

    /**
     * Retrieve product price event data.
     *
     * @param array $data
     *
     * @return array
     */
    public function retrieve(array $data): array;
}
