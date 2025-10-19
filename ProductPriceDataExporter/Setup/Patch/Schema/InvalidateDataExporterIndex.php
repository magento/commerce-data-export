<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ProductPriceDataExporter\Setup\Patch\Schema;

use Magento\CatalogDataExporter\Model\Indexer\IndexInvalidationManager;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InvalidateDataExporterIndex implements SchemaPatchInterface
{
    /**
     * @param SchemaSetupInterface $schemaSetup
     * @param IndexInvalidationManager $invalidationManager
     */
    public function __construct(
        private readonly SchemaSetupInterface $schemaSetup,
        private IndexInvalidationManager $invalidationManager
    ) {}

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->schemaSetup->startSetup();

        $this->invalidationManager->invalidate('recalculate_prices');

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
