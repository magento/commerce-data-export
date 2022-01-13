<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider;

use Magento\CatalogDataExporter\Model\Provider\Category\Formatter\FormatterInterface;
use Magento\CatalogDataExporter\Model\Provider\EavAttributes\EntityEavAttributesResolver;
use Magento\CatalogDataExporter\Model\Query\CategoryMainQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Categories main data provider
 */
class Categories
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CategoryMainQuery
     */
    private $categoryMainQuery;

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityEavAttributesResolver
     */
    private $entityEavAttributesResolver;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CategoryMainQuery $categoryMainQuery
     * @param FormatterInterface $formatter
     * @param LoggerInterface $logger
     * @param EntityEavAttributesResolver $entityEavAttributesResolver
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CategoryMainQuery $categoryMainQuery,
        FormatterInterface $formatter,
        LoggerInterface $logger,
        EntityEavAttributesResolver $entityEavAttributesResolver
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->categoryMainQuery = $categoryMainQuery;
        $this->formatter = $formatter;
        $this->logger = $logger;
        $this->entityEavAttributesResolver = $entityEavAttributesResolver;
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
    public function get(array $values) : array
    {
        $output = [];
        $queryArguments = [];
        $mappedCategories = [];
        $attributesData = [];

        try {
            foreach ($values as $value) {
                $scope = $value['scopeId'] ?? Store::DEFAULT_STORE_ID;
                $queryArguments[$scope][$value['categoryId']] = $value['attribute_ids'] ?? [];
            }

            $connection = $this->resourceConnection->getConnection();
            foreach ($queryArguments as $scopeId => $categoryData) {
                $cursor = $connection->query(
                    $this->categoryMainQuery->getQuery(\array_keys($categoryData), $scopeId ?: null)
                );

                while ($row = $cursor->fetch()) {
                    $mappedCategories[$row['storeViewCode']][$row['categoryId']] = $row;
                    $attributesData[$row['storeViewCode']][$row['categoryId']] = $categoryData[$row['categoryId']];
                }
            }

            foreach ($mappedCategories as $storeCode => $categories) {
                $output[] = \array_map(function ($row) {
                    return $this->formatter->format($row);
                }, \array_replace_recursive(
                    $categories,
                    $this->entityEavAttributesResolver->resolve($attributesData[$storeCode], $storeCode)
                ));
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve category data');
        }

        return !empty($output) ? \array_merge(...$output) : [];
    }
}
