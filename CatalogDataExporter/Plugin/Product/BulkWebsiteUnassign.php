<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogDataExporter\Plugin\Product;

use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\CatalogDataExporter\Model\Indexer\MarkRemovedEntities;
use Magento\CatalogDataExporter\Model\Query\ProductExporterFeedQuery;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;

/**
 * Handle bulk operation for websites unassign for products list
 */
class BulkWebsiteUnassign
{
    private const REMOVE_ACTION = 'remove';

    private MarkRemovedEntities $markRemovedEntities;
    private FeedIndexMetadata $feedIndexMetadata;
    private ProductExporterFeedQuery $productExporterFeedQuery;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param MarkRemovedEntities $markRemovedEntities
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param ProductExporterFeedQuery $productExporterFeedQuery
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        MarkRemovedEntities $markRemovedEntities,
        FeedIndexMetadata $feedIndexMetadata,
        ProductExporterFeedQuery $productExporterFeedQuery,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->markRemovedEntities = $markRemovedEntities;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->productExporterFeedQuery = $productExporterFeedQuery;
        $this->logger = $logger;
    }

    /**
     * Set is_deleted value to 1 for product export entities when websites were unassigned via bulk operation
     *
     * @param ProductAction $subject
     * @param $result
     * @param array $productIds
     * @param array $websiteIds
     * @param string $type
     * @return void
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateWebsites(ProductAction $subject, $result, $productIds, $websiteIds, $type)
    {
        try {
            if ($type === self::REMOVE_ACTION && !empty($websiteIds)) {
                $deletedFeedItems = [];
                $feedItems = $this->productExporterFeedQuery->getFeedItems($productIds, $websiteIds);
                foreach ($feedItems as $itemToDelete) {
                    $deletedFeedItems[$itemToDelete['store_view_code']][] = $itemToDelete['id'];
                }
                foreach ($deletedFeedItems as $storeCode => $ids) {
                    $this->markRemovedEntities->execute($ids, $storeCode, $this->feedIndexMetadata);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Data Exporter exception has occurred: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
        return $result;
    }
}
