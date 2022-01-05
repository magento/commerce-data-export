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

/**
 * Handle bulk operation for websites unassign for products list
 */
class BulkWebsiteUnassign
{
    private const REMOVE_ACTION = 'remove';
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
     * Set is_deleted value to 1 for product export entities when websites were unassigned via bulk operation
     *
     * @param ProductAction $subject
     * @param null $result
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
    }
}
