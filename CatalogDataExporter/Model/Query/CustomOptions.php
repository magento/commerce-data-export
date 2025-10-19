<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
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
        $storeId = null !== $arguments['storeViewCode']
            ? (int) $this->storeRepository->get($arguments['storeViewCode'])->getId()
            // Default store ID if nothing is provided
            : (int) $this->storeRepository->getById(0)->getId();
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

        $select->joinLeft(
            ['option_price' => $productOptionPriceTable],
            'option_price.option_id = main_table.option_id AND ' . $connection->quoteInto(
                'option_price.store_id = ?',
                $storeId
            ),
            [
                'price',
                'price_type'
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

        $select->join(
            ['option_title' => $productOptionTitleTable],
            'option_title.option_id = main_table.option_id',
            ['title']
        )->where(
            'option_title.store_id = ?',
            $storeId
        );
    }
}
