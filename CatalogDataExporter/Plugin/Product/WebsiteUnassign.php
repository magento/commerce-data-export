<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogDataExporter\Plugin\Product;

use Magento\Catalog\Model\ResourceModel\Product\Website\Link;
use Magento\CatalogDataExporter\Model\Query\ProductExporterFeedQuery;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\CatalogDataExporter\Model\Indexer\MarkRemovedEntities;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;

/**
 * Handle websites unassign for product
 */
class WebsiteUnassign
{
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
     * Set is_deleted value to 1 for product export entity when website unassigned
     *
     * @param Link $subject
     * @param int $productId
     * @param string[] $websiteIds
     * @return null
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeUpdateProductWebsite(
        Link $subject,
        int $productId,
        array $websiteIds
    ) {
        try {
            $originWebsites = $subject->getWebsiteIdsByProductId($productId);
            $deleteInWebsites = array_diff($originWebsites, $websiteIds);
            if (!empty($deleteInWebsites)) {
                $deletedFeedItems = [];
                $feedItems = $this->productExporterFeedQuery->getFeedItems([$productId], $deleteInWebsites);
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
        return null;
    }
}
