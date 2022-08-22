<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GroupedProductDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link;

/**
 * Build Select object to fetch grouped product option values
 */
class GroupedProductOptionValuesQuery
{
    /**
     * Catalog product entity table
     */
    private const PRODUCT_ENTITY_TABLE = 'catalog_product_entity';

    /**
     * Catalog grouped product link entity table
     */
    private const PRODUCT_LINK_ENTITY_TABLE = 'catalog_product_link';

    /**
     * Catalog grouped product link attribute entity table
     */
    private const PRODUCT_LINK_ATTRIBUTE_ENTITY_TABLE = 'catalog_product_link_attribute';

    /**
     * Position attribute code
     */
    private const POSITION_ATTRIBUTE_CODE = 'position';

    /**
     * Quantity attribute code
     */
    private const QUANTITY_ATTRIBUTE_CODE = 'qty';

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
     * Get query for provider
     *
     * @param array $productIds
     * @return Select
     */
    public function getQuery(array $productIds) : Select
    {
        $connection = $this->resourceConnection->getConnection();
        $catalogProductTable = $this->resourceConnection->getTableName(self::PRODUCT_ENTITY_TABLE);
        $catalogProductLinkTable = $this->resourceConnection->getTableName(self::PRODUCT_LINK_ENTITY_TABLE);
        $catalogProductLinkQtyTable = $this->resourceConnection->getTableName([self::PRODUCT_LINK_ATTRIBUTE_ENTITY_TABLE, 'decimal']);
        $catalogProductLinkPositionTable = $this->resourceConnection->getTableName([self::PRODUCT_LINK_ATTRIBUTE_ENTITY_TABLE, 'int']);

        return $connection->select()
            ->from(
                [
                    'main_table' => $catalogProductLinkTable,
                ],
                []
            )
            ->join(
                [
                    'cpe_parent' => $catalogProductTable,
                ],
                'cpe_parent.entity_id = main_table.product_id',
                []
            )
            ->join(
                [
                    'cpe_product' => $catalogProductTable,
                ],
                'cpe_product.entity_id = main_table.linked_product_id',
                []
            )
            ->join(
                [
                    'qty_table' => $catalogProductLinkQtyTable,
                ],
                \sprintf('qty_table.link_id = main_table.link_id AND qty_table.product_link_attribute_id = %1$d',
                $this->getProductLinkAttributeId(self::QUANTITY_ATTRIBUTE_CODE)
                ),
                []
            )
            ->join(
                [
                    'position_table' => $catalogProductLinkPositionTable,
                ],
                \sprintf('position_table.link_id = main_table.link_id AND position_table.product_link_attribute_id = %1$d',
                    $this->getProductLinkAttributeId(self::POSITION_ATTRIBUTE_CODE)
                ),
                []
            )
            ->where('cpe_parent.entity_id IN (?)', $productIds)
            ->columns([
                'id' => 'main_table.linked_product_id',
                'sku' => 'cpe_product.sku',
                'sort_order' => 'position_table.value',
                'qty' => 'qty_table.value',
                'parent_id' => 'main_table.product_id',
            ]);
    }

    /**
     * Get product link attribute id for specific attribute
     *
     * @param string $attribute
     * @return int
     */
    private function getProductLinkAttributeId(string $attribute) : int
    {
        $connection = $this->resourceConnection->getConnection();
        $catalogProductLinkAttributeTable = $this->resourceConnection->getTableName(self::PRODUCT_LINK_ATTRIBUTE_ENTITY_TABLE);

        $productLinkAttributeId = (int)$connection->fetchOne(
            $connection->select()
                ->from(['cpla' => $catalogProductLinkAttributeTable], ['product_link_attribute_id'])
                ->where('cpla.product_link_attribute_code = :attribute AND cpla.link_type_id = :link_type_id'),
            [
                'attribute' => $attribute,
                'link_type_id' => Link::LINK_TYPE_GROUPED,
            ]
        );

        return $productLinkAttributeId;
    }
}
