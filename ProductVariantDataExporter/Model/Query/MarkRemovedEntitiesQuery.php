<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\DataExporter\Model\Query\MarkRemovedEntitiesQuery as DefaultMarkRemovedEntitiesQuery;
use Magento\Framework\Exception\LocalizedException;

/**
 * Mark removed entities select query provider
 */
class MarkRemovedEntitiesQuery extends DefaultMarkRemovedEntitiesQuery
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;

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
            ->where('f.product_id IN (?)', $ids)
            ->where(
                \sprintf(
                    'removed_product.entity_id IS NULL 
                    OR unassigned_product.entity_id IS NULL'
                )
            );
    }
}
