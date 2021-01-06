<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Products custom option price query provider class
 */
class CustomOptionPrice
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Retrieve query for custom option price.
     *
     * @param array $optionIds
     * @param int $scopeId
     *
     * @return Select
     */
    public function getQuery(array $optionIds, int $scopeId): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['cpop' => $this->resourceConnection->getTableName('catalog_product_option_price')], [])
            ->columns(
                [
                    'option_id' => 'cpop.option_id',
                    'price' => 'cpop.price',
                    'price_type' => 'cpop.price_type',
                ]
            )
            ->where('cpop.option_id IN (?)', $optionIds)
            ->where('cpop.store_id = ?', $scopeId);
    }
}
