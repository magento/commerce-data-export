<?php
/**
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
 */

namespace Magento\CatalogInventoryDataExporter\Model\Plugin;

use Magento\Indexer\Model\IndexerFactory;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateLegacyStockStatus;

/**
 * Covers case when Stock Status for Legacy Inventory has been changed after placing order
 * Note: consumer "inventory.reservations.updateSalabilityStatus" should be running
 */
class LegacyStockStatusUpdater
{
    private ScheduleProductUpdate $scheduleProductUpdate;

    /**
     * @param ScheduleProductUpdate $scheduleProductUpdate
     */
    public function __construct(
        ScheduleProductUpdate $scheduleProductUpdate
    ) {
        $this->scheduleProductUpdate = $scheduleProductUpdate;
    }

    /**
     * @param UpdateLegacyStockStatus $subject
     * @param $result
     * @param array $dataForUpdate
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        UpdateLegacyStockStatus $subject,
        $result,
        array $dataForUpdate
    ): void {
        $this->scheduleProductUpdate->execute(array_keys($dataForUpdate));
    }
}
