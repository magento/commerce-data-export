<?php
/**
 * Copyright 2023 Adobe
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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
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
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreRepository $storeRepository
     * @param MetadataPool|null $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StoreRepository $storeRepository,
        ?MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeRepository = $storeRepository;
        $this->metadataPool = $metadataPool
            ?? ObjectManager::getInstance()->get(MetadataPool::class);
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
        $storeId = null !== $arguments['storeViewCode']
            ? (int)$this->storeRepository->get($arguments['storeViewCode'])->getId()
            // Default store ID if nothing is provided
            : (int)$this->storeRepository->getById(0)->getId();
        $optionIds = $arguments['option_ids'];
        $select = $connection->select()
            ->from(['custom_option_value' => $mainTable], ['option_id', 'option_type_id', 'sort_order', 'sku']);
        $select->where('option_id IN(?)', $optionIds);
        $this->addTitleToSelect($select, $storeId);
        $this->addPriceToSelect($select, $storeId);
        return $select;
    }

    /**
     * Retrieve product options select with titles and prices
     *
     * @param array $arguments
     * @return Select
     * @throws NoSuchEntityException
     */
    public function queryValuesByProductIds(array $arguments): Select
    {
        $optionValueTableName = $this->resourceConnection->getTableName('catalog_product_option_type_value');
        $optionTableName = $this->resourceConnection->getTableName('catalog_product_option');
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        /** @var EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();
        $connection = $this->resourceConnection->getConnection();
        $storeId = !isset($arguments['storeViewCode'])
            // Default store ID if nothing is provided
            ? (int)$this->storeRepository->getById(Store::DEFAULT_STORE_ID)->getId()
            : (int)$this->storeRepository->get($arguments['storeViewCode'])->getId();
        $productIds = $arguments['productIds'];
        // If product will have different options for it versions (see Staging functionality for details)
        // it may have all versions options values in results
        $select = $connection->select()
            ->from(
                ['product' => $productTable],
                ['product_id' => 'entity_id']
            )
            ->joinInner(
                ['custom_option' => $optionTableName],
                \sprintf('custom_option.product_id = product.%s', $linkField),
                ['option_type' => 'type']
            )
            ->joinInner(
                ['custom_option_value' => $optionValueTableName],
                'custom_option_value.option_id = custom_option.option_id',
                ['option_id', 'option_type_id', 'sort_order' , 'sku']
            );

        $select->where('product.entity_id IN (?)', $productIds);
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

        $joinExpr = 'value_price.option_type_id = custom_option_value.option_type_id AND ' .
            $connection->quoteInto(
                'value_price.store_id = ?',
                $storeId
            );
        $select->joinLeft(
            ['value_price' => $optionTypeTable],
            $joinExpr,
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
        $optionTitleTable = $this->resourceConnection->getTableName('catalog_product_option_type_title');

        $select->join(
            ['value_title' => $optionTitleTable],
            'value_title.option_type_id = custom_option_value.option_type_id',
            ['title']
        )->where(
            'value_title.store_id = ?',
            $storeId
        );
    }
}
