<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;

/**
 * Query for catalog exporter table
 */
class ProductExporterFeedQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var FeedIndexMetadata
     */
    private $feedIndexMetadata;

    /**
     * @param ResourceConnection $resourceConnection
     * @param FeedIndexMetadata $feedIndexMetadata
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        FeedIndexMetadata $feedIndexMetadata
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->feedIndexMetadata = $feedIndexMetadata;
    }

    /**
     * Get feed items by website ids
     *
     * @param array $productIds
     * @param array $websiteIds
     * @return array
     */
    public function getFeedItems(array $productIds, array $websiteIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['cdep' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                ['cdep.id', 'cdep.store_view_code']
            );
        if (empty($productIds) || empty($websiteIds)) {
            return [];
        }
        $select->joinInner(
            ['catalog_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'catalog_product_entity.sku = cdep.sku',
            []
        )->joinInner(
            ['store' => $this->resourceConnection->getTableName('store')],
            'store.code = cdep.store_view_code',
            []
        )->where(
            'catalog_product_entity.entity_id IN (?)',
            $productIds
        )->where(
            'store.website_id IN (?)',
            $websiteIds
        );
        return $connection->fetchAll($select);
    }
}
