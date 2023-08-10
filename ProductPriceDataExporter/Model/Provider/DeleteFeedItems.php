<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Provider;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime;

/**
 * Mark items from price feed as deleted if _new_feed_items_ not present in feed table. Covers cases:
 * - customer group price unassigned from product
 * - catalog rule price unassigned/outdated
 */
class DeleteFeedItems
{
    private ResourceConnection $resourceConnection;

    private SerializerInterface $serializer;

    private CommerceDataExportLoggerInterface $logger;

    private DateTime $dateTime;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SerializerInterface $serializer
     * @param CommerceDataExportLoggerInterface $logger
     * @param DateTime $dateTime
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SerializerInterface $serializer,
        CommerceDataExportLoggerInterface $logger,
        DateTime $dateTime
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
    }

    /**
     * Execute data
     *
     * @param array $newFeedItems
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(array $newFeedItems): array
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

        $select = $connection
            ->select()
            ->from(['f' => $this->resourceConnection->getTableName('catalog_data_exporter_product_prices')])
            ->where('CONCAT_WS("-", product_id, website_id, customer_group_code) not IN(?)', $ids)
            ->where('product_id IN (?)', \array_unique($productIds))
            ->where('website_id IN (?)', \array_unique($websiteIds));
        $cursor = $this->resourceConnection->getConnection()->query($select);
        $output = [];
        while ($row = $cursor->fetch()) {
            try {
                $feed = $this->serializer->unserialize($row['feed_data']);
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning(
                    'Prices Feed error: can not parse feed data for deleted items: ' . $e->getMessage()
                );
                continue;
            }
            // mark feed as deleted
            $feed['deleted'] = true;
            // set updated at
            $feed['updatedAt'] = $this->dateTime->formatDate(time());
            $output[] = $feed;
        }

        return $output;
    }
}
