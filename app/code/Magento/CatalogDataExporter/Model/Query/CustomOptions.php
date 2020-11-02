<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreRepository;

/**
 * Class CustomOptions
 *
 * Retrieve SELECT statement for custom options
 */
class CustomOptions
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
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreRepository $storeRepository
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StoreRepository $storeRepository,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeRepository = $storeRepository;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Retrieve prepared MySQL select of all options available for products
     *
     * @param array $arguments
     * @return Select
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function query(array $arguments): Select
    {
        $mainTable = $this->resourceConnection->getTableName('catalog_product_option');
        $connection = $this->resourceConnection->getConnection();
        $storeId = (int) $this->storeRepository->get($arguments['storeViewCode'])->getId();
        $productIds = $arguments['product_ids'];
        $select = $connection->select()
            ->from(['main_table' => $mainTable]);
        $select->where('cpe.entity_id IN(?)', $productIds);
        $select->order('sort_order');
        $select->order('option_id');
        $this->addTitleToSelect($select, $storeId);
        $this->addLinkFieldRelation($select);
        $this->addPriceToSelect($select, $storeId);
        return $select;
    }

    /**
     * Adding relation between ID field and row_id field
     *
     * @param Select $select
     * @return void
     */
    private function addLinkFieldRelation(Select $select): void
    {
        $cpe = $this->resourceConnection->getTableName('catalog_product_entity');
        $select->joinInner(
            ['cpe' => $cpe],
            sprintf(
                'cpe.%s = main_table.product_id',
                $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField()
            ),
            ['entity_id' => 'cpe.entity_id', 'product_sku' => 'cpe.sku']
        );
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
        $productOptionPriceTable = $this->resourceConnection->getTableName('catalog_product_option_price');
        $connection = $this->resourceConnection->getConnection();
        $priceExpr = $connection->getCheckSql(
            'store_option_price.price IS NULL',
            'default_option_price.price',
            'store_option_price.price'
        );
        $priceTypeExpr = $connection->getCheckSql(
            'store_option_price.price_type IS NULL',
            'default_option_price.price_type',
            'store_option_price.price_type'
        );

        $select->joinLeft(
            ['default_option_price' => $productOptionPriceTable],
            'default_option_price.option_id = main_table.option_id AND ' . $connection->quoteInto(
                'default_option_price.store_id = ?',
                \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ),
            ['default_price' => 'price', 'default_price_type' => 'price_type']
        )->joinLeft(
            ['store_option_price' => $productOptionPriceTable],
            'store_option_price.option_id = main_table.option_id AND ' . $connection->quoteInto(
                'store_option_price.store_id = ?',
                $storeId
            ),
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
        $productOptionTitleTable = $this->resourceConnection->getTableName('catalog_product_option_title');
        $connection = $this->resourceConnection->getConnection();
        $titleExpr = $connection->getCheckSql(
            'store_option_title.title IS NULL',
            'default_option_title.title',
            'store_option_title.title'
        );

        $select->join(
            ['default_option_title' => $productOptionTitleTable],
            'default_option_title.option_id = main_table.option_id',
            ['default_title' => 'title']
        )->joinLeft(
            ['store_option_title' => $productOptionTitleTable],
            'store_option_title.option_id = main_table.option_id AND ' . $connection->quoteInto(
                'store_option_title.store_id = ?',
                $storeId
            ),
            ['store_title' => 'title', 'title' => $titleExpr]
        )->where(
            'default_option_title.store_id = ?',
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );
    }
}
