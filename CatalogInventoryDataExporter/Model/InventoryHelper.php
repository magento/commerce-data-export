<?php
/**
 * Copyright 2024 Adobe
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
