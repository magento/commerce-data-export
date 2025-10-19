<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Setup\Patch\Schema;

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
            'catalog_data_exporter_product_variants' => 'catalog_data_exporter_product_variants'
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
