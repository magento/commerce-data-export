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
use Magento\Store\Model\StoreManagerInterface;

/**
 * Product downloadable link query for catalog data exporter
 */
class ProductDownloadableLinksQuery
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->storeManager = $storeManager;
    }

    /**
     * Form and return query to get download links for product
     *
     * @param array $productIds
     * @param int $storeId
     * @return Select
     * @throws \Exception
     */
    public function getQuery(array $productIds, int $storeId): Select
    {
        $connection = $this->resourceConnection->getConnection();

        $linksTable = [
            'main_table' => $this->resourceConnection->getTableName('downloadable_link')
        ];
        $catalogProductTable = [
            'product' => $this->resourceConnection->getTableName('catalog_product_entity')
        ];
        $storeLinkTitle = [
            'store_title' => $this->resourceConnection->getTableName('downloadable_link_title'),
        ];
        $defaultStoreLinkTitle = [
            'default_store_title' => $this->resourceConnection->getTableName('downloadable_link_title'),
        ];
        $storeLinkPrice = [
            'store_price' => $this->resourceConnection->getTableName('downloadable_link_price'),
        ];
        $defaultStoreLinkPrice = [
            'default_store_price' => $this->resourceConnection->getTableName('downloadable_link_price'),
        ];

        /** @var EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();

        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();

        return $connection->select()
            ->from($linksTable, [])
            ->columns($this->getAvailableAttributes())
            ->join(
                $catalogProductTable,
                \sprintf('product.%1$s = main_table.product_id', $linkField),
                []
            )
            ->joinLeft(
                $storeLinkTitle,
                $connection->quoteInto(
                    'store_title.link_id = main_table.link_id AND store_title.store_id = ?',
                    $storeId
                ),
                []
            )
            ->joinLeft(
                $defaultStoreLinkTitle,
                'default_store_title.link_id = main_table.link_id AND (default_store_title.store_id = 0)',
                []
            )
            ->joinLeft(
                $storeLinkPrice,
                $connection->quoteInto(
                    'store_price.link_id = main_table.link_id AND store_price.website_id = ?',
                    $websiteId
                ),
                []
            )
            ->joinLeft(
                $defaultStoreLinkPrice,
                'default_store_price.link_id = main_table.link_id AND default_store_price.website_id = 0',
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
            'link_id' => 'main_table.link_id',
            'entity_id' => 'product.entity_id',
            'sort_order' => 'main_table.sort_order',
            'sample_url' => new \Zend_Db_Expr('IFNULL(main_table.sample_url, main_table.sample_file)'),
            'title' => new \Zend_Db_Expr('IFNULL(store_title.title, default_store_title.title)'),
            'price' => new \Zend_Db_Expr('IFNULL(store_price.price, default_store_price.price)'),
        ];
    }
}
