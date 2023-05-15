<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogDataExporter\Plugin\Eav\Attribute;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogDataExporter\Model\Indexer\IndexInvalidationManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Store\Model\Store;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;

/**
 * MySQL trigger does not call in case of cascade deleting (by FK), as a result product not re-indexed when product
 * attribute deleted (attribute deleted from eav_attribute table directly) @see https://bugs.mysql.com/bug.php?id=11472
 *
 * This plugin reindex affected products if indexer mode is set to "Update On Schedule"
 * Since operation is triggered from Admin UI we cannot allow long operation, as a result plugins works in 2 modes:
 * - invalidate product exporter indexer if amount of affected products > 10000
 * - add affected products to changelog to for partial reindex if amount of affected products < 10000
 */
class ProductAttributeDelete
{
    private const MAX_PRODUCTS_FOR_INSERT = 10000;

    private ResourceConnection $resourceConnection;
    private MetadataPool $metadataPool;
    private IndexInvalidationManager $invalidationManager;
    private IndexerRegistry $indexerRegistry;
    private CommerceDataExportLoggerInterface $logger;
    private int $maxProductsPerInsert;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @param IndexInvalidationManager $invalidationManager
     * @param IndexerRegistry $indexerRegistry
     * @param CommerceDataExportLoggerInterface $logger
     * @param int $maxProductsPerInsert
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        IndexInvalidationManager $invalidationManager,
        IndexerRegistry $indexerRegistry,
        CommerceDataExportLoggerInterface $logger,
        int $maxProductsPerInsert = self::MAX_PRODUCTS_FOR_INSERT
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->invalidationManager = $invalidationManager;
        $this->indexerRegistry = $indexerRegistry;
        $this->logger = $logger;
        $this->maxProductsPerInsert = $maxProductsPerInsert;
    }

    /**
     * @param Attribute $attribute
     */
    public function beforeDelete(
        Attribute $attribute
    ) {
        $indexer = $this->indexerRegistry->get('catalog_data_exporter_products');
        if (!$indexer->isScheduled()) {
            return ;
        }

        try {
            $connection = $this->resourceConnection->getConnection('indexer');
            $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
            $linkField = $metadata->getLinkField();
            $query = $connection->select()
                ->from(['attribute' => $attribute->getBackendTable()], [])
                ->where('attribute.attribute_id=?', $attribute->getId())
                ->where('attribute.store_id=?', Store::DEFAULT_STORE_ID);

            $countQuery = clone $query;
            $countQuery->columns('count(1)');
            if ($connection->fetchOne($countQuery) > $this->maxProductsPerInsert) {
                $this->invalidationManager->invalidate('product_attribute_deleted');
            } else {
                $query->joinInner(
                    ['product' => $connection->getTableName('catalog_product_entity')],
                    \sprintf('attribute.%1$s = product.%1$s', $linkField),
                    ['product.entity_id']
                );
                $connection->query($query->insertFromSelect(
                    $connection->getTableName($indexer->getView()->getChangelog()->getName()),
                    ['entity_id']
                ));
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Product Data Exporter: attribute deletion error' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
