<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Provider;

use Magento\Framework\App\ResourceConnection;

/**
 * Mark items from price feed as deleted if _new_feed_items_ not present in feed table. Covers cases:
 * - customer group price unassigned from product
 * - catalog rule price unassigned/outdated
 */
class DeleteFeedItems
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection,
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param array $newFeedItems
     * @return void
     */
    public function execute(array $newFeedItems): void
    {
        $connection = $this->resourceConnection->getConnection();

        $ids = [];
        $productIds = [];
        $websiteIds = [];
        foreach ($newFeedItems as $feedItem) {
            $ids[] = $feedItem['productId'] . '-' . $feedItem['websiteId'] . '-' . $feedItem['customerGroupCode'];
            $productIds[] = $feedItem['productId'];
            $websiteIds[] = $feedItem['websiteId'];
        }

        $connection->update(
            $this->resourceConnection->getTableName('catalog_data_exporter_product_prices'),
            ['is_deleted' => new \Zend_Db_Expr('1')],
            [
                'CONCAT_WS("-", product_id, website_id, customer_group_code) not IN(?)' => $ids,
                'product_id IN (?)' => \array_unique($productIds),
                'website_id IN (?)' => \array_unique($websiteIds),
            ]
        );
    }
}
