<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\DataExporter\Model\Query\MarkRemovedEntitiesQuery as DefaultMarkRemovedEntitiesQuery;

/**
 * Mark removed entities select query provider
 */
class MarkRemovedEntitiesQuery extends DefaultMarkRemovedEntitiesQuery
{
    private const STATUS_ATTRIBUTE_CODE = "status";

    /**
     * We should process tier prices for QTY 1 only
     */
    private const TIER_PRICE_QTY = "1";

    private const DISABLED_STATUS_VALUE = "2";

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Config $eavConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Config $eavConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        parent::__construct($resourceConnection);
    }

    /**
     * Get select query for marking removed entities
     *
     * @param array $ids
     * @param FeedIndexMetadata $metadata
     *
     * @return Select
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuery(array $ids, FeedIndexMetadata $metadata): Select
    {
        $statusAttribute = $this->eavConfig->getAttribute('catalog_product', self::STATUS_ATTRIBUTE_CODE);
        $statusAttributeId = $statusAttribute ? $statusAttribute->getId() : null;
        $connection = $this->resourceConnection->getConnection();
        $catalogProductTable = $this->resourceConnection->getTableName($metadata->getSourceTableName());
        $productEntityJoinField = $connection->getAutoIncrementField($catalogProductTable);
        return $connection->select()
            ->joinLeft(
                ['p' => $catalogProductTable],
                \sprintf('f.%s = p.%s', $metadata->getFeedTableField(), $metadata->getSourceTableField()),
                [
                    'is_deleted' => new \Zend_Db_Expr('1')
                ]
            )
            ->joinLeft(
                ['w' => $this->resourceConnection->getTableName('store_website')],
                'f.website_id = w.website_id',
                []
            )
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName('store')],
                'f.website_id = s.website_id',
                []
            )
            ->joinLeft(
                ['pi' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                \sprintf(
                    'f.product_id = pi.%s 
                    AND pi.attribute_id = %s
                    AND pi.store_id = s.store_id',
                    $productEntityJoinField,
                    $statusAttributeId
                ),
                []
            )
            ->joinLeft(
                ['t' => $this->resourceConnection->getTableName('catalog_product_entity_tier_price')],
                \sprintf(
                    'p.%s = t.%s
                    AND f.customer_group_code in(t.customer_group_id, sha1(t.customer_group_id))
                    AND t.qty = %s
                    AND f.website_id in (t.website_id, (select website_id from `%s` where is_default > 0))',
                    $productEntityJoinField,
                    $productEntityJoinField,
                    self::TIER_PRICE_QTY,
                    $this->resourceConnection->getTableName('store_website')
                ),
                []
            )
            ->joinLeft(
                ['pw' => $this->resourceConnection->getTableName('catalog_product_website')],
                'f.product_id = pw.product_id AND w.website_id = pw.website_id',
                []
            )
            ->joinLeft(
                ['cg' => $this->resourceConnection->getTableName('customer_group')],
                't.customer_group_id = cg.customer_group_id',
                []
            )
            ->where(
                \sprintf('f.%s IN (?)', $metadata->getFeedTableField()),
                $ids
            )->where(
                \sprintf(
                    'p.%s IS NULL OR pw.website_id IS NULL OR pi.value = %s 
                    OR (cg.customer_group_id IS NULL AND f.customer_group_code != 0)',
                    $metadata->getSourceTableField(),
                    self::DISABLED_STATUS_VALUE
                )
            );
    }
}
