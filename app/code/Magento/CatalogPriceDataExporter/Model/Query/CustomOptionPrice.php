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
     * @param int|null $lastKnownId
     * @param int|null $batchSize
     * @return Select
     */
    public function getQuery(array $optionIds, int $scopeId, ?int $lastKnownId = 0, ?int $batchSize = null): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['cpop' => $this->resourceConnection->getTableName('catalog_product_option_price')], [])
            ->columns(
                [
                    'option_id' => 'cpop.option_id',
                    'price' => 'cpop.price',
                    'price_type' => 'cpop.price_type',
                ]
            )
            ->where('cpop.store_id = ?', $scopeId)
            ->where('cpop.option_id > ?', $lastKnownId)
            ->order('cpop.option_id');
        if (!empty($optionIds)) {
            $select->where('cpop.option_id IN (?)', $optionIds);
        }
        if (null !== $batchSize) {
            $select->limit($batchSize);
        }
        return $select;
    }
}
