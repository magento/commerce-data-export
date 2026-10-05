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

namespace AdobeCommerce\CategoryProductDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Provider\Category\CategoryUrlPathBuilder;
use Magento\CatalogDataExporter\Model\StoreIdResolver;
use AdobeCommerce\CategoryProductDataExporter\Model\Query\ProductCategoryDataQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Product categories data provider.
 *
 * Reads from catalog_category_product (write-side table) instead of the index and
 * expands direct assignments with anchor-inherited rows in PHP.
 */
class CategoryData
{
    /**
     * Offset added to the minimum child position for anchor-inherited category rows.
     *
     * Matches the catalog_category_product_index convention
     * IFNULL(ccp2.position, MIN(ccp.position) + 10000): a product rolled up into an anchor from its
     * descendants carries the minimum of those descendant positions plus this offset.
     */
    private const ANCHOR_PRODUCT_POSITION_OFFSET = 10000;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductCategoryDataQuery $productCategoryDataQuery
     * @param LoggerInterface $logger
     * @param CategoryUrlPathBuilder $urlPathBuilder
     * @param StoreIdResolver $storeIdResolver
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly ProductCategoryDataQuery $productCategoryDataQuery,
        private readonly LoggerInterface $logger,
        private readonly CategoryUrlPathBuilder $urlPathBuilder,
        private readonly StoreIdResolver $storeIdResolver
    ) {
    }

    /**
     * Retrieve product category relationships (with anchor inheritance) for the given products.
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values): array
    {
        $connection = $this->resourceConnection->getConnection();
        $output = [];

        if (empty($values)) {
            return $output;
        }

        try {
            $productIds = [];
            $storeViewCodes = [];
            foreach ($values as $value) {
                $productIds[$value['productId']] = $value['productId'];
                $storeViewCodes[$value['storeViewCode']] = $value['storeViewCode'];
            }

            foreach ($storeViewCodes as $storeViewCode) {
                $storeId = $this->storeIdResolver->getStoreId($storeViewCode);
                $rootCategoryPath = $this->productCategoryDataQuery->getRootCategoryPath($storeId);

                $results = $connection->fetchAll(
                    $this->productCategoryDataQuery->getQuery($productIds, $rootCategoryPath, $storeId)
                );

                $results = $this->filterInactiveCategories($results, $storeId);
                $results = $this->expandWithAnchorCategories($results, $storeId);

                $pathsByEntityId = [];
                foreach ($results as $result) {
                    $pathsByEntityId[(int)$result['categoryId']] = $result['path'];
                }
                $urlPaths = $this->urlPathBuilder->resolveUrlPaths($pathsByEntityId, $storeViewCode);

                foreach ($results as $result) {
                    $key = implode('-', [$storeViewCode, $result['productId'], $result['categoryId']]);
                    $categoryData = $result;
                    unset($categoryData['path']);
                    $path = $urlPaths[(int)$result['categoryId']] ?? null;
                    if ($path === null) {
                        $this->logger->error(sprintf(
                            'CDE01-22 Unable to resolve url_path for category %d'
                            . ' with path "%s" for store view "%s"',
                            $result['categoryId'],
                            $result['path'],
                            $storeViewCode
                        ));
                        continue;
                    }
                    $categoryData['categoryPath'] = $path;

                    $output[$key]['productId'] = $result['productId'];
                    $output[$key]['storeViewCode'] = $storeViewCode;
                    $output[$key]['categoryData'] = $categoryData;
                }
            }
        } catch (\Throwable $exception) {
            throw new UnableRetrieveData(
                sprintf('Unable to retrieve category data for products: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        return $output;
    }

    /**
     * Expands direct assignments with rows for every ancestor that has is_anchor=1.
     *
     * For each directly assigned category the path (e.g. "1/2/5/10") is walked from
     * depth 2 upwards (skipping root "1" and Default Category "2", which are always
     * excluded by the level > 1 filter in the query). Ancestors with is_anchor=1 for
     * $storeId receive a synthetic row with productPosition set to the anchor position
     * (matching the catalog_category_product_index convention). Direct assignments always
     * take precedence over synthetic anchor rows.
     *
     * @param array $results
     * @param int $storeId
     * @return array
     */
    private function expandWithAnchorCategories(array $results, int $storeId): array
    {
        if (empty($results)) {
            return $results;
        }

        $ancestorPaths = $this->collectAncestorPaths($results);
        if (empty($ancestorPaths)) {
            return $results;
        }

        $anchorIds = $this->productCategoryDataQuery->getAnchorCategoryIds(
            array_keys($ancestorPaths),
            $storeId
        );
        // Only active anchors receive rollup rows: core rolls a product up onto an ancestor solely when
        // that anchor itself is active (the intermediate path between child and anchor is not checked).
        $anchorIds = array_values(array_intersect(
            $anchorIds,
            $this->productCategoryDataQuery->getActiveCategoryIds($anchorIds, $storeId)
        ));
        if (empty($anchorIds)) {
            return $results;
        }

        $anchorRows = $this->buildAnchorRows($results, array_flip($anchorIds), $ancestorPaths);

        return empty($anchorRows) ? $results : array_merge($results, array_values($anchorRows));
    }

    /**
     * Collects ancestor category ids mapped to their category paths from direct assignment paths.
     *
     * Paths look like "1/2/<id>/.../<categoryId>"; ancestors are segments[2..-2]
     * (root "1" and Default Category "2" are skipped, consistent with the level > 1 query filter).
     *
     * @param array $results
     * @return array
     */
    private function collectAncestorPaths(array $results): array
    {
        $ancestorPaths = [];
        foreach ($results as $result) {
            $segments = explode('/', $result['path']);
            $ancestorSegments = array_slice($segments, 2, -1);
            if (empty($ancestorSegments)) {
                continue;
            }
            $prefix = $segments[0] . '/' . $segments[1];
            foreach ($ancestorSegments as $segment) {
                $prefix .= '/' . $segment;
                $ancestorPaths[(int)$segment] ??= $prefix;
            }
        }

        return $ancestorPaths;
    }

    /**
     * Builds synthetic feed rows for anchor ancestors, deduplicated against direct assignments.
     *
     * @param array $results
     * @param array $anchorIdSet
     * @param array $ancestorPaths
     * @return array
     */
    private function buildAnchorRows(array $results, array $anchorIdSet, array $ancestorPaths): array
    {
        $directKeys = [];
        foreach ($results as $result) {
            $directKeys[$result['productId'] . '-' . $result['categoryId']] = true;
        }

        $anchorRows = [];
        $minChildPosition = [];
        foreach ($results as $result) {
            $childPosition = (int)$result['productPosition'];
            $segments = explode('/', $result['path']);
            foreach (array_slice($segments, 2, -1) as $segment) {
                $ancestorId = (int)$segment;
                if (!isset($anchorIdSet[$ancestorId])) {
                    continue;
                }
                $dedupeKey = $result['productId'] . '-' . $ancestorId;
                // A direct assignment to the anchor category takes precedence over inheritance.
                if (isset($directKeys[$dedupeKey])) {
                    continue;
                }
                // Aggregate the minimum descendant position per (product, anchor), mirroring
                // MIN(ccp.position) in catalog_category_product_index.
                if (!isset($minChildPosition[$dedupeKey]) || $childPosition < $minChildPosition[$dedupeKey]) {
                    $minChildPosition[$dedupeKey] = $childPosition;
                }
                $anchorRows[$dedupeKey] = [
                    'productId'       => $result['productId'],
                    'categoryId'      => (string)$ancestorId,
                    'productPosition' => 0,
                    'path'            => $ancestorPaths[$ancestorId],
                ];
            }
        }

        foreach ($anchorRows as $dedupeKey => &$anchorRow) {
            $anchorRow['productPosition'] = $minChildPosition[$dedupeKey] + self::ANCHOR_PRODUCT_POSITION_OFFSET;
        }
        unset($anchorRow);

        return $anchorRows;
    }

    /**
     * Keeps only direct assignments whose own (directly-assigned) category is active for the store view.
     *
     * Mirrors catalog_category_product_index, which gates a direct row on the is_active of the category
     * the product is assigned to -- NOT on intermediate ancestors. An active leaf under an inactive
     * intermediate ancestor is therefore still exported (the anchor rollup that follows applies its own
     * is_active check on the anchor in expandWithAnchorCategories()). Runs on the direct assignments
     * BEFORE anchor expansion so a dropped direct row is never rolled up.
     *
     * Done in PHP, not a JOIN in getQuery(): it reuses the same store-scoped resolver used for is_anchor
     * (getActiveCategoryIds), keeping category-flag resolution single-sourced.
     *
     * @param array $results
     * @param int $storeId
     * @return array
     */
    private function filterInactiveCategories(array $results, int $storeId): array
    {
        if (empty($results)) {
            return $results;
        }

        $categoryIds = [];
        foreach ($results as $result) {
            $categoryIds[(int)$result['categoryId']] = true;
        }
        $activeIds = array_flip(
            $this->productCategoryDataQuery->getActiveCategoryIds(array_keys($categoryIds), $storeId)
        );

        return array_values(array_filter(
            $results,
            fn (array $result): bool => isset($activeIds[(int)$result['categoryId']])
        ));
    }
}
