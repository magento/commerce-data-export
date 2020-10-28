<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Query\Category;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Category;

/**
 * Category children data query
 */
class ChildCategoriesQuery
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
     * Return children ids of category
     *
     * @param Category $category
     *
     * @return array
     */
    public function getAllChildrenIds(Category $category): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $this->resourceConnection->getTableName('catalog_category_entity')],
            'entity_id'
        )->where('main_table.path LIKE ?', "{$category->getPath()}/%");

        return $connection->fetchCol($select);
    }
}
