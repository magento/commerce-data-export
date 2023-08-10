<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreRepository;

/**
 * Class CustomOptionValues
 *
 * Retrieve custom option values for specific options
 */
class CustomOptionValues
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StoreRepository
     */
    private $storeRepository;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreRepository $storeRepository
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StoreRepository $storeRepository
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Retrieve product options select with titles and prices
     *
     * @param array $arguments
     * @return Select
     * @throws NoSuchEntityException
     */
    public function query(array $arguments): Select
    {
        $mainTable = $this->resourceConnection->getTableName('catalog_product_option_type_value');
        $connection = $this->resourceConnection->getConnection();
        $storeId = (int) $this->storeRepository->get($arguments['storeViewCode'])->getId();
        $optionIds = $arguments['option_ids'];
        $select = $connection->select()
            ->from(['main_table' => $mainTable], ['option_id', 'option_type_id', 'sort_order', 'sku']);
        $select->where('option_id IN(?)', $optionIds);
        $this->addTitleToSelect($select, $storeId);
        $this->addPriceToSelect($select, $storeId);
        return $select;
    }

    /**
     * Add prices to custom options
     *
     * @param Select $select
     * @param int $storeId
     * @return void
     */
    private function addPriceToSelect(Select $select, int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $optionTypeTable = $this->resourceConnection->getTableName('catalog_product_option_type_price');
        $priceExpr = $connection->getCheckSql(
            'store_value_price.price IS NULL',
            'default_value_price.price',
            'store_value_price.price'
        );
        $priceTypeExpr = $connection->getCheckSql(
            'store_value_price.price_type IS NULL',
            'default_value_price.price_type',
            'store_value_price.price_type'
        );

        $joinExprDefault = 'default_value_price.option_type_id = main_table.option_type_id AND ' .
            $connection->quoteInto(
                'default_value_price.store_id = ?',
                \Magento\Store\Model\Store::DEFAULT_STORE_ID
            );
        $joinExprStore = 'store_value_price.option_type_id = main_table.option_type_id AND ' .
            $connection->quoteInto('store_value_price.store_id = ?', $storeId);
        $select->joinLeft(
            ['default_value_price' => $optionTypeTable],
            $joinExprDefault,
            ['default_price' => 'price', 'default_price_type' => 'price_type']
        )->joinLeft(
            ['store_value_price' => $optionTypeTable],
            $joinExprStore,
            [
                'store_price' => 'price',
                'store_price_type' => 'price_type',
                'price' => $priceExpr,
                'price_type' => $priceTypeExpr
            ]
        );
    }

    /**
     * Add option titles to select: default or store view option titles
     *
     * @param Select $select
     * @param int $storeId
     * @return void
     */
    private function addTitleToSelect(Select $select, int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $optionTitleTable = $this->resourceConnection->getTableName('catalog_product_option_type_title');
        $titleExpr = $connection->getCheckSql(
            'store_value_title.title IS NULL',
            'default_value_title.title',
            'store_value_title.title'
        );

        $joinExpr = 'store_value_title.option_type_id = main_table.option_type_id AND ' .
            $connection->quoteInto('store_value_title.store_id = ?', $storeId);
        $select->join(
            ['default_value_title' => $optionTitleTable],
            'default_value_title.option_type_id = main_table.option_type_id',
            ['default_title' => 'title']
        )->joinLeft(
            ['store_value_title' => $optionTitleTable],
            $joinExpr,
            ['store_title' => 'title', 'title' => $titleExpr]
        )->where(
            'default_value_title.store_id = ?',
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );
    }
}
