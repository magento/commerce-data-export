<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Build Select object to fetch configurable product links
 */
class LinkedAttributesQuery
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
     * Get linked attributes query
     *
     * @param int $productId
     * @return Select
     */
    public function getQuery(int $productId): Select
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(
                ['cpsl' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                []
            )
            ->joinInner(
                ['cpsa' => $this->resourceConnection->getTableName('catalog_product_super_attribute')],
                'cpsa.product_id = cpsl.parent_id',
                []
            )
            ->joinInner(
                ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                'ea.attribute_id = cpsa.attribute_id',
                []
            )
            ->columns(
                [
                    'attributeCode' => 'ea.attribute_code',
                ]
            )
            ->where('cpsl.product_id = ?', $productId);
    }
}
