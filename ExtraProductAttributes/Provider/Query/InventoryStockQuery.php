<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\ExtraProductAttributes\Provider\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Inventory Stock data query builder
 */
class InventoryStockQuery
{
    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {}

    /**
     * Get query for provider
     *
     * @param array $productIds
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getQuery(array $productIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(['product' => $this->resourceConnection->getTableName('catalog_product_entity')], [])
            ->joinInner(
                [
                    's' => $this->resourceConnection->getTableName('cataloginventory_stock_item'),
                ],
                's.product_id = product.entity_id',
                []
            )->joinInner(
                [
                    'w' => $this->resourceConnection->getTableName('store_website'),
                ],
                's.website_id = w.website_id',
                []
            )->where('product.entity_id IN (?)', $productIds)
            ->columns(
                [
                    'productId' => "product.entity_id",
                    'sku' => "product.sku",
                    'websiteCode' => 'w.code',
                    'manageStock' => 's.manage_stock',
                    'manageStock_config' => 's.use_config_manage_stock',
                    'cartMinQty_config' => 's.use_config_min_sale_qty',
                    'cartMinQty' => 's.min_sale_qty',
                    'cartMaxQty_config' => 's.use_config_max_sale_qty',
                    'cartMaxQty' => 's.max_sale_qty',
                    'backorders' => 's.backorders',
                    'backorders_config' => 's.use_config_backorders',
                    'enableQtyIncrements' => 's.enable_qty_increments',
                    'enableQtyIncrements_config' => 's.use_config_enable_qty_inc',
                    'qtyIncrements' => 's.qty_increments',
                    'qtyIncrements_config' => 's.use_config_qty_increments',
                ]
            );
    }
}
