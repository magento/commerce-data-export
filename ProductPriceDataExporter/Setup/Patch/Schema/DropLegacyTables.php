<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Setup\Patch\Schema;

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
            'catalog_data_exporter_product_prices'
        ];

        return $this->dropTables($tableList);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [RenameOldChangeLogTables::class, UpdateHashConfigDataExporterIndex::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
