<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogDataExporter\Plugin;

use Magento\Setup\Model\FixtureGenerator\SqlCollector;

/**
 * Filter out changelog tables by pattern {*_cl}: fixture generation running in Update on Schedule mode,
 * however it doesn't have knowledge how to hande changelog table
 */
class FilterChangeLogTable
{
    /**
     * Filter out changelog tables by pattern {*_cl}
     *
     * @param SqlCollector $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSql(SqlCollector $subject, array $result): array
    {
        return array_filter($result, static function ($item) {
            return !str_ends_with($item[1], '_cl');
        });
    }
}
