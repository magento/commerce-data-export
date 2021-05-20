<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model;

use Magento\DataExporter\Model\FeedInterface;

/**
 * Interface ProductVariantFeedInterface
 */
interface ProductVariantFeedInterface extends FeedInterface
{
    /**
     * Get feed data by product IDs
     *
     * @param int[] $entityIds
     * @return array
     */
    public function getFeedByProductIds(array $entityIds): array;

    /**
     * Get deleted entities by product IDs
     *
     * @param int[] $ids
     * @return array
     */
    public function getDeletedByProductIds(array $ids): array;
}
