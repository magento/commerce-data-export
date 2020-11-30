<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;

/**
 * Retrieve product relations
 */
class ProductRelationsQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Return the parent ids of product relations
     *
     * @param int[] $ids
     * @return array
     */
    public function getRelationsParentIds(array $ids): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['cpr' => $this->resourceConnection->getTableName('catalog_product_relation')],
            []
        )->join(
            ['cpe' => $catalogProductTable = $this->resourceConnection->getTableName('catalog_product_entity')],
            \sprintf('cpe.%1$s = cpr.parent_id', $connection->getAutoIncrementField($catalogProductTable)),
            ['entity_id']
        )->where(
            sprintf(
                'cpe.entity_id IN ("%1$s") OR cpr.child_id IN ("%1$s")',
                \implode(",", $ids)
            )
        );
        return array_filter($connection->fetchCol($select));
    }
}
