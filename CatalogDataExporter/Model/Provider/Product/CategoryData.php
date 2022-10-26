<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\CatalogDataExporter\Model\Query\ProductCategoryDataQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Product categories data provider
 */
class CategoryData
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductCategoryDataQuery
     */
    private $productCategoryDataQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductCategoryDataQuery $productCategoryDataQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductCategoryDataQuery $productCategoryDataQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productCategoryDataQuery = $productCategoryDataQuery;
        $this->logger = $logger;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values) : array
    {
        $connection = $this->resourceConnection->getConnection();
        $queryArguments = [];
        $output = [];
        try {
            foreach ($values as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            foreach ($queryArguments['storeViewCode'] as $storeViewCode) {
                $results = $connection->fetchAll(
                    $this->productCategoryDataQuery->getQuery($queryArguments, $storeViewCode)
                );
                foreach ($results as $result) {
                    $key = implode('-', [$storeViewCode, $result['productId'], $result['categoryId']]);
                    $output[$key]['productId'] = $result['productId'];
                    $output[$key]['storeViewCode'] = $storeViewCode;
                    $output[$key]['categoryData'] = $result;
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve category data for products');
        }
        return $output;
    }
}
