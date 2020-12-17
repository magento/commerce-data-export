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
 * Products custom selectable option price query provider class
 */
class CustomOptionTypePrice
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
     * @param array $optionTypeIds
     * @param int $scopeId
     * @param int|null $lastKnownId
     * @param int|null $batchSize
     * @return Select
     */
    public function getQuery(array $optionTypeIds, int $scopeId, ?int $lastKnownId = 0, ?int $batchSize = null): Select
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['cpotp' => $this->resourceConnection->getTableName('catalog_product_option_type_price')],
                []
            )
            ->join(
                ['cpotv' => $this->resourceConnection->getTableName('catalog_product_option_type_value')],
                'cpotv.option_type_id = cpotp.option_type_id',
                []
            )
            ->columns(
                [
                    'option_id' => 'cpotv.option_id',
                    'option_type_id' => 'cpotp.option_type_id',
                    'price' => 'cpotp.price',
                    'price_type' => 'cpotp.price_type',
                ]
            )
            ->where('cpotp.store_id = ?', $scopeId)
            ->where('cpotp.option_type_id > ?', $lastKnownId)
            ->order('cpotp.option_type_id');
        if (!empty($optionTypeIds)) {
            $select->where('cpotp.option_type_id IN (?)', $optionTypeIds);
        }
        if (null !== $batchSize) {
            $select->limit($batchSize);
        }
        return $select;
    }
}
