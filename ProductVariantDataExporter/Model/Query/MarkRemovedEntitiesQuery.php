<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Query;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
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

    private const STATUS_DISABLED = Status::STATUS_DISABLED;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var Config
     */
    private Config $eavConfig;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Config $eavConfig
     */
    public function __construct(ResourceConnection $resourceConnection, Config $eavConfig)
    {
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
     */
    public function getQuery(array $ids, FeedIndexMetadata $metadata): Select
    {
        $fieldName = $metadata->getSourceTableField();
        $connection = $this->resourceConnection->getConnection();

        $catalogProductTable = $this->resourceConnection->getTableName($metadata->getSourceTableName());
        $productEntityJoinField = $connection->getAutoIncrementField($catalogProductTable);

        $statusAttribute = $this->eavConfig->getAttribute('catalog_product', self::STATUS_ATTRIBUTE_CODE);
        $statusAttributeId = $statusAttribute?->getId();

        return $connection->select()
            ->joinLeft(
                ['e' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                \sprintf('f.parent_id = e.%s', $fieldName),
                ['is_deleted' => new \Zend_Db_Expr('1')]
            )
            ->joinLeft(
                ['p' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                \sprintf('f.parent_id = p.%s', $fieldName),
                []
            )
            ->joinLeft(
                ['s' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                \sprintf('s.product_id = e.%s AND s.parent_id = p.%s', $fieldName, $fieldName),
                []
            )
            ->joinLeft(
                ['pi' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                \sprintf(
                    'f.product_id = pi.%s 
                    AND pi.attribute_id = %s
                    AND pi.store_id = 0',
                    $productEntityJoinField,
                    $statusAttributeId
                ),
                []
            )
            ->where(
                \sprintf(
                    'f.product_id IN (?) OR e.%s IS NULL OR pi.value = %s',
                    $fieldName,
                    self::STATUS_DISABLED
                ), $ids
            );
    }
}
