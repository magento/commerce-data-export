<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogDataExporter\Setup\Patch\Data;

use Magento\DataExporter\Service\IndexInvalidationManager;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InvalidateDataExporterIndex implements DataPatchInterface
{
    private IndexInvalidationManager $invalidationManager;

    /**
     * @param SchemaSetupInterface $schemaSetup

     * @param IndexInvalidationManager|null $invalidationManager
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        private readonly SchemaSetupInterface $schemaSetup,
        ?IndexInvalidationManager $invalidationManager = null
    ) {
        $this->invalidationManager = $invalidationManager
            ?? ObjectManager::getInstance()->get(IndexInvalidationManager::class);
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->schemaSetup->startSetup();

        $this->invalidationManager->invalidate('fix_feed_data_update');

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
