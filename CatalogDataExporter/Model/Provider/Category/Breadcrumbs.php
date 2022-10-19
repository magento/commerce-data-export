<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category;

use Magento\CatalogDataExporter\Model\Query\Eav\CategoryAttributeQueryBuilder;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Category breadcrumbs data provider
 */
class Breadcrumbs
{
    /**
     * Category path slice count
     */
    private const PATH_SLICE_COUNT = 2;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CategoryAttributeQueryBuilder
     */
    private $categoryAttributeQueryBuilder;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param CategoryAttributeQueryBuilder $categoryAttributeQueryBuilder
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        CategoryAttributeQueryBuilder $categoryAttributeQueryBuilder
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->categoryAttributeQueryBuilder = $categoryAttributeQueryBuilder;
    }

    /**
     * Get provider data
     *
     * @param array $values
     *
     * @return array
     *
     * @throws UnableRetrieveData
     */
    public function get(array $values): array
    {
        $queryArguments = [];
        $output = [];

        foreach ($values as $value) {
            $categoryPath = $value['path'];

            if (null === $categoryPath) {
                continue;
            }

            $pathArray = \array_slice(\explode('/', $categoryPath), self::PATH_SLICE_COUNT);
            \array_pop($pathArray);

            foreach ($pathArray as $parentCategoryId) {
                $queryArguments[$value['storeViewCode']][$parentCategoryId][] = $value['categoryId'];
            }
        }

        try {
            foreach ($queryArguments as $storeViewCode => $categoriesMapping) {
                $output[] = $this->formatData(
                    $this->getCategoriesForStore(\array_keys($categoriesMapping), $storeViewCode),
                    $categoriesMapping
                );
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve category breadcrumbs data');
        }

        return !empty($output) ? \array_merge(...$output) : [];
    }

    /**
     * Format data
     *
     * @param array $categories
     * @param array $categoriesMapping
     *
     * @return array
     */
    private function formatData(array $categories, array $categoriesMapping) : array
    {
        $output = [];

        foreach ($categories as $entityId => $categoryData) {
            foreach ($categoriesMapping[$entityId] as $childId) {
                if (!isset(
                    $categoryData['category_name'],
                    $categoryData['category_level'],
                    $categoryData['category_url_key'],
                    $categoryData['category_url_path'])
                ) {
                    $this->logger->warning(
                        \sprintf(
                            "Category Feed Exporter: breadcrumbs data is empty: %s",
                            \var_export($categoryData, true)
                        )
                    );
                    continue;
                }
                $output[] = [
                    'categoryId' => $childId,
                    'storeViewCode' => $categoryData['store_view_code'],
                    'breadcrumbs' => [
                        'categoryId' => $categoryData['category_id'],
                        'categoryName' => $categoryData['category_name'],
                        'categoryLevel' => $categoryData['category_level'],
                        'categoryUrlKey' => $categoryData['category_url_key'],
                        'categoryUrlPath' => $categoryData['category_url_path'],
                    ]
                ];
            }
        }

        return $output;
    }

    /**
     * Retrieve categories data for specific store
     *
     * @param int[] $entityIds
     * @param string $storeViewCode
     *
     * @return array
     */
    private function getCategoriesForStore(array $entityIds, string $storeViewCode) : array
    {
        $categories = [];
        $select = $this->categoryAttributeQueryBuilder->build(
            $entityIds,
            ['level', 'name', 'url_key', 'url_path'],
            $storeViewCode
        );

        $cursor = $this->resourceConnection->getConnection()->query($select);
        while ($row = $cursor->fetch()) {
            if (!isset($row['attribute_code'])) {
                continue;
            }
            $categories[$row['entity_id']]['store_view_code'] = $storeViewCode;
            $categories[$row['entity_id']]['category_' . $row['attribute_code']] = $row['value'];
            $categories[$row['entity_id']]['category_id'] = $row['entity_id'];
            $categories[$row['entity_id']]['category_level'] = $row['level'];
        }

        return $categories;
    }
}
