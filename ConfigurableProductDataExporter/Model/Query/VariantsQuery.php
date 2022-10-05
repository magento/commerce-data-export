<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Query;

use Magento\CatalogDataExporter\Model\Resolver\PriceTableResolver;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Product variant query builder
 */
class VariantsQuery
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
     * VariantsQuery constructor.
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
        $connection = $this->resourceConnection->getConnection();
        $joinField = $connection->getAutoIncrementField($this->getTable('catalog_product_entity'));

        $select = $connection->select()
            ->from(['cpsl' => $this->getTable('catalog_product_super_link')], [])
            ->joinInner(
                ['cpsa' => $this->getTable('catalog_product_super_attribute')],
                'cpsa.product_id = cpsl.parent_id',
                []
            )
            ->joinInner(
                ['cpe' => $this->getTable('catalog_product_entity')],
                'cpe.entity_id = cpsl.product_id',
                []
            )
            ->joinInner(
                ['cpeParent' => $this->getTable('catalog_product_entity')],
                sprintf('cpeParent.%1$s = cpsl.parent_id', $joinField),
                []
            )
            ->joinInner(
                ['cpip' => $this->getTable('catalog_product_index_price')],
                'cpip.entity_id = cpe.entity_id',
                []
            )
            ->joinInner(
                ['s' => $this->getTable('store')],
                's.website_id = cpip.website_id'
            )
            ->columns(
                [
                    'storeViewCode' => 's.code',
                    'productId' => 'cpeParent.entity_id',
                    'sku' => 'cpe.sku',
                    'price' => 'cpip.price',
                    'finalPrice' => 'cpip.final_price',
                ]
            )
            ->where('cpeParent.entity_id IN (?)', $productIds)
            ->where('cpip.customer_group_id = 0')
            ->distinct();

        return $select;
    }
}
