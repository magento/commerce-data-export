<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BundleProductDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Build Select object to fetch bundle product option values
 */
class BundleProductOptionValuesQuery
{
    /**
     * Catalog product entity main table
     */
    private const PRODUCT_ENTITY_TABLE = 'catalog_product_entity';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var int
     */
    private $productNameAttributeId;

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
     * @param string $storeViewCode
     *
     * @return Select
     */
    public function getQuery(array $productIds, string $storeViewCode) : Select
    {
        $connection = $this->resourceConnection->getConnection();
        $catalogProductTable = $this->resourceConnection->getTableName(self::PRODUCT_ENTITY_TABLE);
        $catalogProductVarcharTable = $this->resourceConnection->getTableName([self::PRODUCT_ENTITY_TABLE, 'varchar']);
        $productEntityJoinField = $connection->getAutoIncrementField($catalogProductTable);
        $storeValueTableAlias = 'name_store';
        $defaultValueTableAlias = 'name_default';

        return $connection->select()
            ->from(
                [
                    'main_table' => $this->resourceConnection->getTableName('catalog_product_bundle_selection'),
                ],
                []
            )
            ->join(
                [
                    's' => $this->resourceConnection->getTableName('store'),
                ],
                $connection->quoteInto('s.code = ?', $storeViewCode),
                []
            )
            ->join(
                [
                    'cpe_parent' => $catalogProductTable,
                ],
                \sprintf('cpe_parent.%1$s = main_table.parent_product_id', $productEntityJoinField),
                []
            )
            ->join(
                [
                    'cpe_product' => $catalogProductTable,
                ],
                'cpe_product.entity_id = main_table.product_id',
                []
            )
            ->joinLeft(
                [
                    $storeValueTableAlias => $catalogProductVarcharTable,
                ],
                \sprintf(
                    '%1$s.%2$s = cpe_product.%2$s AND %1$s.attribute_id = %3$d AND %1$s.store_id = s.store_id',
                    $storeValueTableAlias,
                    $productEntityJoinField,
                    $this->getProductNameAttributeId()
                ),
                []
            )
            ->joinLeft(
                [
                    $defaultValueTableAlias => $catalogProductVarcharTable,
                ],
                \sprintf(
                    '%1$s.%2$s = cpe_product.%2$s AND %1$s.attribute_id = %3$d AND %1$s.store_id = 0',
                    $defaultValueTableAlias,
                    $productEntityJoinField,
                    $this->getProductNameAttributeId()
                ),
                []
            )
            ->where('cpe_parent.entity_id IN (?)', $productIds)
            ->columns([
                'id' => 'main_table.selection_id',
                'sort_order' => 'main_table.position',
                'default' => 'main_table.is_default',
                'attribute_id' => 'name_default.attribute_id',
                'qty' => 'main_table.selection_qty',
                'qty_mutability' => 'main_table.selection_can_change_qty',
                'option_id' => 'main_table.option_id',
                'parent_id' => 'main_table.parent_product_id',
                'label' => $connection->getIfNullSql('name_store.value', 'name_default.value'),
                'sku' => 'cpe_product.sku',
            ]);
    }

    /**
     * Get product name attribute id
     *
     * @return int
     */
    private function getProductNameAttributeId() : int
    {
        if (null === $this->productNameAttributeId) {
            $connection = $this->resourceConnection->getConnection();

            $this->productNameAttributeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from(['a' => $this->resourceConnection->getTableName('eav_attribute')], ['attribute_id'])
                    ->join(
                        ['t' => $this->resourceConnection->getTableName('eav_entity_type')],
                        't.entity_type_id = a.entity_type_id',
                        []
                    )
                    ->where('t.entity_table = ?', self::PRODUCT_ENTITY_TABLE)
                    ->where('a.attribute_code = ?', 'name')
            );
        }

        return $this->productNameAttributeId;
    }
}
