<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\CatalogDataExporter\Model\Query\ProductCategoryQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Psr\Log\LoggerInterface;

/**
 * Product categories data provider
 */
class Categories
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductCategoryQuery
     */
    private $productCategoryQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductCategoryQuery $productCategoryQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductCategoryQuery $productCategoryQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productCategoryQuery = $productCategoryQuery;
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
        try {
            foreach ($values as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            $result = $connection->fetchAll($this->productCategoryQuery->getQuery($queryArguments));
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve categories data');
        }
        return $result;
    }
}
