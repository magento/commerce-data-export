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

/**
 * Handle websites unassign for product
 */
class WebsiteUnassign
{
    /**
     * @var MarkRemovedEntities
     */
    private $markRemovedEntities;

    /**
     * @var FeedIndexMetadata
     */
    private $feedIndexMetadata;

    /**
     * @var ProductExporterFeedQuery
     */
    private $productExporterFeedQuery;

    /**
     * @param MarkRemovedEntities $markRemovedEntities
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param ProductExporterFeedQuery $productExporterFeedQuery
     */
    public function __construct(
        MarkRemovedEntities $markRemovedEntities,
        FeedIndexMetadata $feedIndexMetadata,
        ProductExporterFeedQuery $productExporterFeedQuery
    ) {
        $this->markRemovedEntities = $markRemovedEntities;
        $this->feedIndexMetadata = $feedIndexMetadata;
        $this->productExporterFeedQuery = $productExporterFeedQuery;
    }

    /**
     * Set is_deleted value to 1 for product export entity when website unassigned
     *
     * @param Link $subject
     * @param int $productId
     * @param string[] $websiteIds
     * @return array
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeUpdateProductWebsite(
        Link $subject,
        int $productId,
        array $websiteIds
    ): array {
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
        return [$productId, $websiteIds];
    }
}
