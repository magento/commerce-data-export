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

namespace Magento\CatalogDataExporter\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class RenameOldChangeLogTables implements SchemaPatchInterface
{
    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        private readonly SchemaSetupInterface $schemaSetup
    ) {}

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->schemaSetup->startSetup();

        $tableList = [
            'catalog_data_exporter_categories_cl' => 'cde_categories_feed_cl',
            'catalog_data_exporter_product_attributes_cl' => 'cde_product_attributes_feed_cl',
            'catalog_data_exporter_products_cl' => 'cde_products_feed_cl',
        ];
        $connection = $this->schemaSetup->getConnection();
        foreach ($tableList as $oldTableName => $newTableName) {
            $oldTableName = $this->schemaSetup->getTable($oldTableName);
            $newTableName = $this->schemaSetup->getTable($newTableName);
            if ($connection->isTableExists($oldTableName)) {
                $this->schemaSetup->getConnection()->renameTable($oldTableName, $newTableName);
            }
        }

        $this->schemaSetup->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
