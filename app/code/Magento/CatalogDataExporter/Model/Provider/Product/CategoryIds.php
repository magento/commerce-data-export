<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\CatalogDataExporter\Model\Query\ProductCategoryIdsQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Psr\Log\LoggerInterface;

/**
 * Product categories data provider
 */
class CategoryIds
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductCategoryIdsQuery
     */
    private $productCategoryIdsQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductCategoryIdsQuery $productCategoryIdsQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductCategoryIdsQuery $productCategoryIdsQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productCategoryIdsQuery = $productCategoryIdsQuery;
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
                    $this->productCategoryIdsQuery->getQuery($queryArguments, $storeViewCode)
                );
                if (!empty($results)) {
                    foreach ($results as $result) {
                        $output[] = $result;
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve categories data');
        }
        return $output;
    }
}
