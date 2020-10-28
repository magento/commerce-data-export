<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query\Category;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;

/**
 * Query for marking deleted categories in feed
 */
class DeletedCategoriesQuery
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
     * Update is_deleted column of catalog_data_exporter_categories
     *
     * @return void
     */
    public function updateDeletedFlagQuery() : void
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->joinLeft(
                ['cce' => $this->resourceConnection->getTableName('catalog_category_entity')],
                'cdec.id = cce.entity_id',
                ['is_deleted' => new Expression('1')]
            )
            ->where('cce.entity_id IS NULL');

        $update = $connection->updateFromSelect($select, [
            'cdec' => $this->resourceConnection->getTableName('catalog_data_exporter_categories')
        ]);
        $connection->query($update);
    }
}
