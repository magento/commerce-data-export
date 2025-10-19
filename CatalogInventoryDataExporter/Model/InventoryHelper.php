<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryDataExporter\Model;

use Magento\Framework\Module\ModuleList;

/**
 * Check if MSI is enabled
 */
class InventoryHelper
{
    /**
     * @var ModuleList
     */
    private ModuleList $moduleList;

    /**
     * @param ModuleList $moduleList
     */
    public function __construct(
        ModuleList $moduleList
    ) {
        $this->moduleList = $moduleList;
    }

    /**
     * @return bool
     */
    public function isMSIEnabled(): bool
    {
        return $this->moduleList->getOne('Magento_InventoryIndexer') !== null;
    }
}
