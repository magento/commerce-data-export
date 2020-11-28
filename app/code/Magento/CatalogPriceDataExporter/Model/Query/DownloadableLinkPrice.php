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
     * @param string $linkId
     * @param string $scopeId
     *
     * @return Select
     */
    public function getQuery(string $linkId, string $scopeId): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()
            ->from(['dlp' => $this->resourceConnection->getTableName('downloadable_link_price')], [])
            ->columns(
                [
                    'value' => 'dlp.price',
                ]
            )
            ->where('dlp.link_id = ?', $linkId)
            ->where('dlp.website_id = ?', $scopeId);
    }
}
