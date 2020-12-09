<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogPriceDataExporter\Model\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Products downloadable links query provider class
 */
class DownloadableLinkPrice
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
     * Retrieve query for custom option price.
     *
     * @param array $linkIds
     * @param int $scopeId
     *
     * @return Select
     */
    public function getQuery(array $linkIds, int $scopeId): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['dlp' => $this->resourceConnection->getTableName('downloadable_link_price')], [])
            ->columns(
                [
                    'entity_id' => 'dlp.link_id',
                    'value' => 'dlp.price',
                ]
            )
            ->where('dlp.link_id in (?)', $linkIds)
            ->where('dlp.website_id = ?', $scopeId);
    }
}
