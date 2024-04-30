<?php

/*************************************************************************
 *
 * Copyright 2023 Adobe
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
 * ***********************************************************************
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
