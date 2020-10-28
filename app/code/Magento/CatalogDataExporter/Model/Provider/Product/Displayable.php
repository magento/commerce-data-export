<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Query\UnavailableProductQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Is product displayable data provider
 */
class Displayable
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var UnavailableProductQuery
     */
    private $unavailableProductQuery;

    /**
     * @param ResourceConnection $resourceConnection
     * @param UnavailableProductQuery $unavailableProductQuery
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        UnavailableProductQuery $unavailableProductQuery
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->unavailableProductQuery = $unavailableProductQuery;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values) : array
    {
        $connection = $this->resourceConnection->getConnection();
        $queryArguments = [];

        foreach ($values as $value) {
            $queryArguments['productId'][$value['productId']] = $value['productId'];
            $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
        }
        $unavailable = [];
        foreach ($queryArguments['storeViewCode'] as $storeViewCode) {
            $arguments = [
                'productId' => $queryArguments['productId'],
                'storeViewCode' => $storeViewCode
            ];
            $cursor = $connection->query($this->unavailableProductQuery->getQuery($arguments));
            while ($row = $cursor->fetch()) {
                $unavailable[$storeViewCode][$row['productId']] = true;
            }
        }
        $output = [];
        foreach ($values as $value) {
            $output[] = [
                'productId' => $value['productId'],
                'storeViewCode' => $value['storeViewCode'],
                'displayable' => (
                    $value['status'] === 'Enabled'
                    && in_array($value['visibility'], ['Catalog', 'Search', 'Catalog, Search'])
                    && !isset($unavailable[$value['storeViewCode']][$value['productId']])
                )
            ];
        }
        return $output;
    }
}
