<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Category default sort by data provider
 */
class DefaultSortBy
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CatalogConfig
     */
    private $catalogConfig;

    /**
     * @param LoggerInterface $logger
     * @param CatalogConfig $catalogConfig
     */
    public function __construct(LoggerInterface $logger, CatalogConfig $catalogConfig)
    {
        $this->logger = $logger;
        $this->catalogConfig = $catalogConfig;
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
        try {
            $queryArguments = [];
            $output = [];

            foreach ($values as $value) {
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }

            $defaultSortByOptionByStore = $this->provideDefaultSortByOptionsByStoreViewCodes(
                $queryArguments['storeViewCode']
            );
            $availableSortByAttributesArray = $this->catalogConfig->getAttributeUsedForSortByArray();

            foreach ($values as $value) {
                $defaultSortBy = $value['defaultSortBy'] ?? $defaultSortByOptionByStore[$value['storeViewCode']];
                $categorySortByOptions = $this->validateAndRetrieveCategorySortByOptions(
                    $availableSortByAttributesArray,
                    $value
                );

                if (!isset($categorySortByOptions[$defaultSortBy])) {
                    $defaultSortBy = \array_keys($categorySortByOptions)[0] ?? null;
                }

                $output[] = [
                    'categoryId' => $value['categoryId'],
                    'storeViewCode' => $value['storeViewCode'],
                    'defaultSortBy' => $defaultSortBy,
                ];
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve category default sort by data');
        }

        return $output;
    }

    /**
     * Provide default sort by options by store view codes
     *
     * @param string[] $storeViewCodes
     *
     * @return array
     */
    private function provideDefaultSortByOptionsByStoreViewCodes(array $storeViewCodes) : array
    {
        $sortBy = [];

        foreach ($storeViewCodes as $storeViewCode) {
            $sortBy[$storeViewCode] = $this->catalogConfig->getProductListDefaultSortBy($storeViewCode);
        }

        return $sortBy;
    }

    /**
     * Validate and retrieve category sort by options
     *
     * @param array $sortByAttributes
     * @param array $categoryData
     *
     * @return array
     */
    private function validateAndRetrieveCategorySortByOptions(array $sortByAttributes, array $categoryData) : array
    {
        $availableSortByOptions = [];

        if ($available = \explode(',', $categoryData['availableSortBy'] ?? '')) {
            foreach ($available as $sortBy) {
                if (isset($sortByAttributes[$sortBy])) {
                    $availableSortByOptions[$sortBy] = $sortByAttributes[$sortBy];
                }
            }
        }

        if (empty($availableSortByOptions)) {
            $availableSortByOptions = $sortByAttributes;
        }

        return $availableSortByOptions;
    }
}
