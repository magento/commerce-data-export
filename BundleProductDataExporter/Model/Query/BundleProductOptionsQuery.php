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
 * Build Select object to fetch bundle product options
 */
class BundleProductOptionsQuery
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
     * Get query for provider
     *
     * @param int[] $productIds
     * @param string $storeViewCode
     *
     * @return Select
     */
    public function getQuery(array $productIds, string $storeViewCode) : Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(
                [
                    'main_table' => $this->resourceConnection->getTableName('catalog_product_bundle_option'),
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
            ->joinLeft(
                [
                    'option_value' => $this->resourceConnection->getTableName('catalog_product_bundle_option_value'),
                ],
                \implode(' AND ', [
                    'main_table.option_id = option_value.option_id',
                    'main_table.parent_id = option_value.parent_product_id',
                    'option_value.store_id = s.store_id',
                ]),
                []
            )
            ->joinLeft(
                [
                    'option_value_default' => $this->resourceConnection->getTableName(
                        'catalog_product_bundle_option_value'
                    ),
                ],
                \implode(' AND ', [
                    'main_table.option_id = option_value_default.option_id',
                    'main_table.parent_id = option_value_default.parent_product_id',
                    'option_value_default.store_id = 0',
                ]),
                []
            )
            ->join(
                [
                    'cpe' => $catalogProductTable = $this->resourceConnection->getTableName('catalog_product_entity'),
                ],
                \sprintf('cpe.%1$s = main_table.parent_id', $connection->getAutoIncrementField($catalogProductTable)),
                []
            )
            ->where('cpe.entity_id IN (?)', $productIds)
            ->columns([
                'store_view_code' => 's.code',
                'product_id' => 'cpe.entity_id',
                'render_type' => 'main_table.type',
                'label' => $connection->getIfNullSql('option_value.title', 'option_value_default.title'),
                'required' => 'main_table.required',
                'sort_order' => 'main_table.position',
                'option_id' => 'main_table.option_id',
                'parent_id' => 'main_table.parent_id',
            ]);
    }
}
