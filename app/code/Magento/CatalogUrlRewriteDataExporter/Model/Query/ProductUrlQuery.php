<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewriteDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Class ProductOptionQuery
 */
class ProductUrlQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * ProductUrlQuery constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get resource table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName) : string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * Get query for provider
     *
     * @param array $arguments
     * @return Select
     */
    public function getQuery(array $arguments): Select
    {
        $productIds = isset($arguments['productId']) ? $arguments['productId'] : [];
        $storeViewCodes = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['ur' => $this->getTable('url_rewrite')],
                [
                    'productId' => 'ur.entity_id',
                    'url' => 'ur.request_path'
                ]
            )
            ->join(
                ['s' => $this->getTable('store')],
                'ur.store_id = s.store_id',
                ['storeViewCode' => 's.code']
            )
            ->where('ur.entity_type = ?', 'product')
            ->where('ur.redirect_type = ?', 0)
            ->where('s.code IN (?)', $storeViewCodes)
            ->where('ur.entity_id IN (?)', $productIds)
            ->where('ur.metadata IS NULL');
        return $select;
    }
}
