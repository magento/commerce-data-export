<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
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
