<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ProductReviewSummarySync\Model;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Schedule products for re-export in the products feed.
 *
 * Feed indexers always run "Update by Schedule", so product ids are added to the products feed changelog
 * and exported on the next indexer run. Repeated changes of the same product are exported once.
 */
class ProductFeedUpdateScheduler
{
    private const PRODUCTS_FEED_INDEXER = 'catalog_data_exporter_products';

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param ResourceConnection $resourceConnection
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        private readonly IndexerRegistry $indexerRegistry,
        private readonly ResourceConnection $resourceConnection,
        private readonly CommerceDataExportLoggerInterface $logger
    ) {
    }

    /**
     * Schedule the products returned by the select (single "entity_id" column) for re-export.
     *
     * @param Select $productIdsSelect
     * @return void
     */
    public function scheduleFromSelect(Select $productIdsSelect): void
    {
        try {
            [$changelogTable, $changelogColumn] = $this->getChangelog();
            if ($changelogTable !== null) {
                $this->resourceConnection->getConnection()->query(
                    $productIdsSelect->insertFromSelect($changelogTable, [$changelogColumn], false)
                );
            }
        } catch (\Throwable $e) {
            $this->logWarning($e);
        }
    }

    /**
     * Get the products feed changelog table and column, or nulls when the indexer is not scheduled.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function getChangelog(): array
    {
        $indexer = $this->indexerRegistry->get(self::PRODUCTS_FEED_INDEXER);
        if (!$indexer->isScheduled()) {
            return [null, null];
        }
        $changelog = $indexer->getView()->getChangelog();

        return [$this->resourceConnection->getTableName($changelog->getName()), $changelog->getColumnName()];
    }

    /**
     * Log scheduling error
     *
     * @param \Throwable $exception
     * @return void
     */
    private function logWarning(\Throwable $exception): void
    {
        $this->logger->warning(
            'CDE03-29 Product sync scheduling error on review summary change',
            ['error' => $exception->getMessage(), 'exception' => $exception]
        );
    }
}
