<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model\Provider\Product;

use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Product in stock data provider
 */
class InStock
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CatalogInventoryQuery
     */
    private $catalogInventoryQuery;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CatalogInventoryQuery $catalogInventoryQuery
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CatalogInventoryQuery $catalogInventoryQuery,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->catalogInventoryQuery = $catalogInventoryQuery;
        $this->logger = $logger;
    }

    /**
     * Format output
     *
     * @param array $row
     * @return array
     */
    private function format(array $row) : array
    {
        return [
            'productId' => $row['product_id'],
            'storeViewCode' => $row['storeViewCode'],
            'inStock' => $row['is_in_stock'],
        ];
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
            $output = [];
            foreach ($values as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            $select = $this->catalogInventoryQuery->getInStock($queryArguments);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $output[] = $this->format($row);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve stock data');
        }
        return $output;
    }
}
