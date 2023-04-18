<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogInventoryDataExporter\Model\Plugin;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Indexer\Model\IndexerFactory;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
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
        $result, IndexName $indexName,
        array $dataForUpdate,
        string $connectionName
    ): void {
        $this->scheduleProductUpdate->execute(array_keys($dataForUpdate));
    }
}
