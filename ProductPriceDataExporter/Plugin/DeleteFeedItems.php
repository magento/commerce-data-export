<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Plugin;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Indexer\MarkRemovedEntities;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\ProductPriceDataExporter\Model\Query\MarkRemovedEntitiesQuery;

/**
 * Plugin removes price feeds items instead of marking them on deleted when product:
 * - deleted
 * - disabled
 * - unassigned from website
 */
class DeleteFeedItems
{
    private const PRICE_FEED_NAME = 'productPrices';

    private ResourceConnection $resourceConnection;
    private MarkRemovedEntitiesQuery $markRemovedEntitiesQuery;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MarkRemovedEntitiesQuery $markRemovedEntitiesQuery
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MarkRemovedEntitiesQuery $markRemovedEntitiesQuery,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->markRemovedEntitiesQuery = $markRemovedEntitiesQuery;
        $this->logger = $logger;
    }

    /**
     * @param MarkRemovedEntities $subject
     * @param callable $proceed
     * @param array|int[] $ids
     * @param FeedIndexMetadata $metadata
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        MarkRemovedEntities $subject,
        callable $proceed,
        array $ids,
        FeedIndexMetadata $metadata
    ): void {
        if ($metadata->getFeedName() === self::PRICE_FEED_NAME) {
            try {

                $select = $this->markRemovedEntitiesQuery->getQuery($ids, $metadata);
                $connection = $this->resourceConnection->getConnection();

                $connection->query(
                    $connection->deleteFromSelect($select, 'feed')
                );
            } catch (\Throwable $e) {
                $this->logger->error(
                    sprintf("Cannot delete price feed items. product ids: %s", implode(', ', $ids)),
                    ['exception' => $e]
                );
            }
        } else {
            $proceed($ids, $metadata);
        }
    }
}
