<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\CatalogDataExporter\Model\Resolver\PriceTableResolver;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Class ProductPriceQuery
 *
 * Provides a query to export product prices
 */
class ProductPriceQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var PriceTableResolver
     */
    private $priceTableResolver;

    /**
     * MainProductQuery constructor.
     * @param ResourceConnection $resourceConnection
     * @param PriceTableResolver $priceTableResolver
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        PriceTableResolver $priceTableResolver
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->priceTableResolver = $priceTableResolver;
    }

    /**
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->priceTableResolver->getTableName($tableName);
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @return Select
     */
    public function getQuery(array $arguments) : Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCodes = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['cpp' => $this->getTable('catalog_product_index_price')])
            ->join(
                ['s' => $this->getTable('store')],
                's.website_id = cpp.website_id AND cpp.customer_group_id = 0',
                ['storeViewCode' => 's.code']
            )
            ->columns(
                [
                    'productId' => 'cpp.entity_id',
                    'storeViewCode' => 's.code'
                ]
            )
            ->where('s.code IN (?)', $storeViewCodes)
            ->where('cpp.entity_id IN (?)', $productIds);
        return $select;
    }
}
