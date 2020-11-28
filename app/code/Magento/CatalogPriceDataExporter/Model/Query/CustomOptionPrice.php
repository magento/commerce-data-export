<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

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
     * @param string $optionId
     * @param string $scopeId
     *
     * @return Select
     */
    public function getQuery(string $optionId, string $scopeId): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['cpop' => $this->resourceConnection->getTableName('catalog_product_option_price')], [])
            ->columns(
                [
                    'value' => 'cpop.price',
                    'option_price_type' => 'cpop.price_type',
                ]
            )
            ->where('cpop.option_id = ?', $optionId)
            ->where('cpop.store_id = ?', $scopeId);
    }
}
