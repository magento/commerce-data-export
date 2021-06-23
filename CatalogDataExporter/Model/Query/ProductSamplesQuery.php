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
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Build Select object to fetch downloadable product samples.
 */
class ProductSamplesQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Form and return query to get samples for product
     *
     * @param array $productIds
     * @param string $storeViewCode
     *
     * @return Select
     * @throws \Exception
     */
    public function getQuery(array $productIds, string $storeViewCode): Select
    {
        $connection = $this->resourceConnection->getConnection();

        $downloadableSampleTable = [
            'main_table' => $this->resourceConnection->getTableName('downloadable_sample')
        ];
        $catalogProductTable = [
            'product' => $this->resourceConnection->getTableName('catalog_product_entity')
        ];
        $productSampleTitlesTable = [
            'store_title' => $this->resourceConnection->getTableName('downloadable_sample_title')
        ];
        $productSampleDefaultStoreTitlesTable = [
            'default_store_title' => $this->resourceConnection->getTableName('downloadable_sample_title')
        ];

        /** @var EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();

        return $connection->select()
            ->from($downloadableSampleTable, [])
            ->columns($this->getAvailableAttributes())
            ->join(
                $catalogProductTable,
                \sprintf('product.%1$s = main_table.product_id', $linkField),
                []
            )
            ->joinInner(
                ['s' => $this->resourceConnection->getTableName('store')],
                $connection->quoteInto('s.code = ?', $storeViewCode),
                ['storeViewCode' => 's.code']
            )
            ->joinLeft(
                $productSampleTitlesTable,
                'store_title.sample_id = main_table.sample_id AND store_title.store_id = s.store_id',
                []
            )
            ->joinLeft(
                $productSampleDefaultStoreTitlesTable,
                'default_store_title.sample_id = main_table.sample_id AND (default_store_title.store_id = 0)',
                []
            )
            ->where('product.entity_id IN(?)', $productIds)
            ->order('main_table.sort_order asc');
    }

    /**
     * Get list of supported columns.
     *
     * @return array
     */
    private function getAvailableAttributes(): array
    {
        return [
            'sort_order' => 'main_table.sort_order',
            'title' => new \Zend_Db_Expr('IFNULL(store_title.title, default_store_title.title)'),
            'sample_id' => 'main_table.sample_id',
            'entity_id' => 'product.entity_id',
        ];
    }
}
