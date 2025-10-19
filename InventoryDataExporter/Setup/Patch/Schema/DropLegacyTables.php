<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryDataExporter\Setup\Patch\Schema;

use Magento\DataExporter\Setup\DropLegacyTablesAbstract;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class DropLegacyTables extends DropLegacyTablesAbstract implements SchemaPatchInterface
{
    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $tableList = [
            'inventory_data_exporter_stock_status'
        ];

        return $this->dropTables($tableList);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [RenameOldChangeLogTables::class, InvalidateDataExporterIndex::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
