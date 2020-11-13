<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;

/**
 * Product relations query class
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
     * Return related product relations parent ids
     *
     * @param int[] $ids
     * @return array
     */
    public function getRelationsParentIds(array $ids): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            'catalog_product_relation',
            ['parent_id']
        )->where(
            sprintf('parent_id IN ("%1$s") OR child_id IN ("%1$s")', \implode(",", $ids))
        );
        return array_filter($connection->fetchCol($select));
    }
}
