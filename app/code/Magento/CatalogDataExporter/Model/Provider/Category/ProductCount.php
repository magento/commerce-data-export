<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\DimensionFactory;
use Magento\Framework\Search\Request\IndexScopeResolverInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class ProductCount
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var IndexScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * @var DimensionFactory
     */
    private $dimensionFactory;

    /**
     * @var Visibility
     */
    private $catalogProductVisibility;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * ProductCount constructor.
     * @param ResourceConnection $resourceConnection
     * @param DimensionFactory $dimensionFactory
     * @param Visibility $catalogProductVisibility
     * @param IndexScopeResolverInterface $scopeResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DimensionFactory $dimensionFactory,
        Visibility $catalogProductVisibility,
        IndexScopeResolverInterface $scopeResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->scopeResolver = $scopeResolver;
        $this->dimensionFactory = $dimensionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve product count for all requested categories
     *
     * @param array $values
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(array $values): array
    {
        $categoryIds = [];
        $output = [];

        foreach ($values as $value) {
            $categoryIds[$value['storeViewCode']][] = $value['categoryId'];
        }

        foreach ($categoryIds as $storeViewCode => $ids) {
            $storeId = $this->storeManager->getStore($storeViewCode)->getId();
            foreach ($this->getProductCountForCategories($ids, (int) $storeId) as $categoryId => $count) {
                $output[] = [
                    'categoryId' => (string) $categoryId,
                    'productCount' => (int) $count,
                    'storeViewCode' => $storeViewCode
                ];
            }

        }

        return $output;
    }

    /**
     * Retrieve SQL result of product counts
     *
     * @param array $categoryIds
     * @param int $storeId
     * @return array
     */
    private function getProductCountForCategories(array $categoryIds, int $storeId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $storeDimension = $this->dimensionFactory->create(
            Store::ENTITY,
            (string)$storeId
        );
        $categoryTable = $this->scopeResolver->resolve('catalog_category_product_index', [$storeDimension]);

        $select = $connection->select()
            ->from(
                ['cat_index' => $categoryTable],
                ['category_id' => 'cat_index.category_id', 'count' => 'count(cat_index.product_id)']
            )
            ->where('cat_index.visibility in (?)', $this->catalogProductVisibility->getVisibleInSiteIds())
            ->where('cat_index.category_id in (?)', \array_map('\intval', $categoryIds))
            ->group('cat_index.category_id');

        return $connection->fetchPairs($select);
    }
}
