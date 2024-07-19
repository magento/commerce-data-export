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

namespace Magento\CatalogInventoryDataExporter\Model\Plugin;

use Magento\InventoryIndexer\Model\ResourceModel\UpdateIsSalable;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;

/**
 * Covers case when Stock Status has been changed after placing order
 * Note: consumer "inventory.reservations.updateSalabilityStatus" should be running
 */
class StockStatusUpdater
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
     * @param UpdateIsSalable $subject
     * @param $result
     * @param IndexName $indexName
     * @param array $dataForUpdate
     * @param string $connectionName
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        UpdateIsSalable $subject,
        $result,
        IndexName $indexName,
        array $dataForUpdate,
        string $connectionName
    ): void {
        $this->scheduleProductUpdate->execute(array_keys($dataForUpdate));
    }
}
