<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
