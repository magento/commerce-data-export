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
 * Category canonical url query
 */
class CategoryUrlQuery
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
     * Get query for provider
     *
     * @param array $arguments
     *
     * @return Select
     */
    public function getQuery(array $arguments) : Select
    {
        $categoryIds = isset($arguments['categoryId']) ? $arguments['categoryId'] : [];
        $storeViewCodes = isset($arguments['storeViewCode']) ? $arguments['storeViewCode'] : [];
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(
                ['ur' => $this->resourceConnection->getTableName('url_rewrite')],
                [
                    'categoryId' => 'ur.entity_id',
                    'canonicalUrl' => 'ur.request_path',
                ]
            )
            ->join(
                ['s' => $this->resourceConnection->getTableName('store')],
                'ur.store_id = s.store_id',
                ['storeViewCode' => 's.code']
            )
            ->where('ur.entity_type = ?', 'category')
            ->where('ur.redirect_type = ?', 0)
            ->where('s.code IN (?)', $storeViewCodes)
            ->where('ur.entity_id IN (?)', $categoryIds)
            ->where('ur.metadata IS NULL');
    }
}
