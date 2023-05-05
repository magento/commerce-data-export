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
use Magento\Framework\Exception\LocalizedException;

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
     * @throws LocalizedException
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
            ->from(
                ['f' => $this->resourceConnection->getTableName($metadata->getFeedTableName())]
            )
            ->joinLeft(
                ['removed_product' => $catalogProductTable],
                \sprintf('f.product_id = removed_product.%s', $fieldName),
                []
            )
            ->joinLeft(
                ['parent' => $catalogProductTable],
                'f.parent_id = parent.entity_id',
                []
            )
            ->joinLeft(
                ['link' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                \sprintf('link.product_id = f.product_id AND link.parent_id = parent.%s', $productEntityJoinField),
                []
            )
            ->joinLeft(
                ['unassigned_product' => $catalogProductTable],
                \sprintf(
                    'unassigned_product.%s = link.parent_id and unassigned_product.%s = f.parent_id',
                    $productEntityJoinField,
                    $fieldName
                ),
                []
            )
            ->joinLeft(
                ['disabled_product' => $catalogProductTable],
                \sprintf('f.product_id = disabled_product.%s', $fieldName),
                []
            )
            ->joinLeft(
                ['disabled_product_status' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                \sprintf(
                    'disabled_product_status.%s = disabled_product.%s 
                    AND disabled_product_status.attribute_id = %s
                    AND disabled_product_status.store_id = 0',
                    $productEntityJoinField,
                    $productEntityJoinField,
                    $statusAttributeId,
                ),
                []
            )
            ->where('f.product_id IN (?)', $ids)
            ->where(
                \sprintf(
                    'removed_product.entity_id IS NULL 
                    OR disabled_product_status.value = %d 
                    OR unassigned_product.entity_id IS NULL',
                    self::STATUS_DISABLED
                )
            );
    }
}
