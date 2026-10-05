<?php
/**
 * Copyright 2026 Adobe
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

namespace AdobeCommerce\CategoryProductDataExporter\Plugin\IndexerStatusManager;

use AdobeCommerce\IndexerStatusManager\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adds the category-product index's per-store tables to the list IndexerStatusManager truncates on disable.
 *
 * catalog_category_product / catalog_product_category do not use a single fixed index table: their data
 * lives in per-store-view tables (catalog_category_product_index_store<storeId> and its _replica), created
 * dynamically for each store view, alongside the base table and its replica. A static di.xml list cannot
 * enumerate the per-store tables (their count/ids are install-specific), so this plugin appends them --
 * resolved from the store list at runtime -- to Config::getIndexTables() for those two indexer codes. The
 * engine then truncates the shared table only when both indexers writing it are disabled.
 */
class AddPerStoreCategoryIndexTables
{
    /**
     * Indexers whose data lives in the shared catalog_category_product_index* tables.
     */
    private const CATEGORY_INDEXERS = ['catalog_category_product', 'catalog_product_category'];

    /**
     * Base (logical) name of the category-product index; per-store and replica names derive from it.
     */
    private const BASE_TABLE = 'catalog_category_product_index';

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(private readonly StoreManagerInterface $storeManager)
    {
    }

    /**
     * Appends the base, replica and per-store index tables for the category-product indexers.
     *
     * @param Config $subject
     * @param string[] $result
     * @param string $indexerCode
     * @return string[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIndexTables(Config $subject, array $result, string $indexerCode): array
    {
        if (!in_array($indexerCode, self::CATEGORY_INDEXERS, true)) {
            return $result;
        }

        $tables = $result;
        $tables[] = self::BASE_TABLE;
        $tables[] = self::BASE_TABLE . '_replica';
        foreach ($this->storeManager->getStores() as $store) {
            $perStore = self::BASE_TABLE . '_store' . $store->getId();
            $tables[] = $perStore;
            $tables[] = $perStore . '_replica';
        }

        return array_values(array_unique($tables));
    }
}
