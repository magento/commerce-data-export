<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\DataExporter\Model\Query\MarkRemovedEntitiesQuery as DefaultMarkRemovedEntitiesQuery;

/**
 * Select price feed items that should be removed when product:
 * - product deleted
 * - product disabled
 * - product unassigned from website
 *
 * Remove price for specific customer group handled in provider:
 * @see \Magento\ProductPriceDataExporter\Model\Provider\DeleteFeedItems
 */
class MarkRemovedEntitiesQuery extends DefaultMarkRemovedEntitiesQuery
{
    private const STATUS_ATTRIBUTE_CODE = "status";

    private const DISABLED_STATUS_VALUE = "2";

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var Config
     */
    private Config $eavConfig;

    /**
     * @var MetadataPool
     */
    private MetadataPool $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Config $eavConfig
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Config $eavConfig,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->metadataPool = $metadataPool;
        parent::__construct($resourceConnection);
    }

    /**
     * Get select query for marking removed entities
     *
     * @param array $ids
     * @param FeedIndexMetadata $metadata
     *
     * @return Select
     * @throws LocalizedException
     */
    public function getQuery(array $ids, FeedIndexMetadata $metadata): Select
    {
        $statusAttribute = $this->eavConfig->getAttribute('catalog_product', self::STATUS_ATTRIBUTE_CODE);
        $connection = $this->resourceConnection->getConnection();
        $catalogProductTable = $this->resourceConnection->getTableName($metadata->getSourceTableName());
        $productEntityJoinField = $connection->getAutoIncrementField($catalogProductTable);
        return $connection->select()
            ->from(
                ['feed' => $this->resourceConnection->getTableName($metadata->getFeedTableName())]
            )
            ->joinLeft(
                ['p' => $catalogProductTable],
                \sprintf('feed.%s = p.%s', $metadata->getFeedTableField(), $metadata->getSourceTableField()),
                []
            )
            ->joinLeft(
                ['w' => $this->resourceConnection->getTableName('store_website')],
                'feed.website_id = w.website_id',
                []
            )
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName('store')],
                'feed.website_id = s.website_id',
                []
            )
            ->joinLeft(
                ['pi' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                \sprintf(
                    'p.%s = pi.%s 
                    AND pi.attribute_id = %s
                    AND pi.store_id IN (0, s.store_id)',
                    $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField(),
                    $productEntityJoinField,
                    $statusAttribute?->getId()
                ),
                []
            )
            ->joinLeft(
                ['pw' => $this->resourceConnection->getTableName('catalog_product_website')],
                'feed.product_id = pw.product_id AND w.website_id = pw.website_id',
                []
            )
            ->where(
                \sprintf('feed.%s IN (?)', $metadata->getFeedTableField()),
                $ids
            )->where(
                \sprintf(
                    'p.%s IS NULL OR pw.website_id IS NULL OR pi.value = %s',
                    $metadata->getSourceTableField(),
                    self::DISABLED_STATUS_VALUE
                )
            );
    }
}
