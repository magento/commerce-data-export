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

namespace Magento\InventoryDataExporter\Setup\Patch\Schema;

use Magento\Indexer\Model\IndexerFactory;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InvalidateDataExporterIndex implements SchemaPatchInterface
{
    /**
     * @param SchemaSetupInterface $schemaSetup
     * @param IndexerFactory $indexerFactory
     */
    public function __construct(
        private readonly SchemaSetupInterface $schemaSetup,
        private IndexerFactory $indexerFactory
    ) {}

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->schemaSetup->startSetup();

        $tableIndexersList = [
            'inventory_data_exporter_stock_status' => 'inventory_data_exporter_stock_status'
        ];
        $connection = $this->schemaSetup->getConnection();
        foreach ($tableIndexersList as $legacyTableName => $indexerId) {
            $legacyTableName = $this->schemaSetup->getTable($legacyTableName);
            if ($connection->isTableExists($legacyTableName)
                && !$connection->tableColumnExists($legacyTableName, 'status')) {
                $this->indexerFactory->create()->load($indexerId)->invalidate();
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
        return [RenameOldChangeLogTables::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
