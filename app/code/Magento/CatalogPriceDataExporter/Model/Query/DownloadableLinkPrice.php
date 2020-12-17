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
     * @param int|null $lastKnownId
     * @param int|null $batchSize
     * @return Select
     */
    public function getQuery(array $linkIds, int $scopeId, ?int $lastKnownId = 0, ?int $batchSize = null): Select
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(['dlp' => $this->resourceConnection->getTableName('downloadable_link_price')], [])
            ->columns(
                [
                    'entity_id' => 'dlp.link_id',
                    'value' => 'dlp.price',
                    'link_id' => 'dlp.link_id',
                ]
            )
            ->where('dlp.website_id = ?', $scopeId)
            ->where('dlp.link_id > ?', $lastKnownId)
            ->order('dlp.link_id');

        if (!empty($linkIds)) {
            $select->where('dlp.link_id in (?)', $linkIds);
        }
        if (null !== $batchSize) {
            $select->limit($batchSize);
        }
        return $select;
    }
}
