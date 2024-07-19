<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;

/**
 * Base product data query for catalog data exporter
 */
class ProductMainQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    private $mainTable;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $mainTable
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $mainTable
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->mainTable = $mainTable;
    }

    /**
     * Get query for provider
     *
     * @param array $ids
     * @param int|null $scopeId
     *
     * @return Select
     */
    public function getQuery(array $ids, ?int $scopeId = null) : Select
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $this->resourceConnection->getTableName($this->mainTable)],
                [
                    'sku',
                    'productId' => 'main_table.entity_id',
                    'type' => 'main_table.type_id',
                    'createdAt' => 'main_table.created_at',
                    'updatedAt' => 'main_table.updated_at',
                ]
            );

        if (null === $scopeId) {
            $select->joinCross(
                ['s' => $this->resourceConnection->getTableName('store')],
                ['storeViewCode' => 's.code']
            );
        } else {
            $select->join(
                ['s' => $this->resourceConnection->getTableName('store')],
                $connection->quoteInto('s.store_id = ?', $scopeId),
                ['storeViewCode' => 's.code']
            );
        }

        return $select
            ->join(
                ['cpw' => $this->resourceConnection->getTableName('catalog_product_website')],
                'cpw.website_id = s.website_id AND cpw.product_id = main_table.entity_id',
                []
            )
            ->where('s.store_id != ?', Store::DEFAULT_STORE_ID)
            ->where('main_table.entity_id IN (?)', $ids);
    }
}
