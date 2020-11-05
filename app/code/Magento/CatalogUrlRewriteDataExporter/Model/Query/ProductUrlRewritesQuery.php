<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewriteDataExporter\Model\Query;

use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;

/**
 * Fetch product url rewrites.
 */
class ProductUrlRewritesQuery
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
     * Return query that fetches a list of products' url rewrites.
     *
     * @param int[] $productIds
     * @param int $storeId
     * @return Select
     */
    public function getQuery(array $productIds, int $storeId): Select
    {
        $resourceConnection = $this->resourceConnection;
        $connection = $resourceConnection->getConnection();
        $urlRewritesTable = $resourceConnection->getTableName('url_rewrite');

        return $connection->select()
            ->from(
                ['e' => $urlRewritesTable],
                [
                    \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_ID,
                    \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::REQUEST_PATH,
                    \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::TARGET_PATH,
                ]
            )
            ->where('entity_id IN (?)', $productIds)
            ->where('entity_type = ?', 'product')
            ->where('store_id = ?', $storeId);
    }
}
