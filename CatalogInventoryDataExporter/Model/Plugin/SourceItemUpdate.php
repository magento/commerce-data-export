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

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Covers case when Source Item updated in the Admin Panel which may trigger Stock Status change
 */
class SourceItemUpdate
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
     * @param SourceItemsSaveInterface $subject
     * @param null $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(SourceItemsSaveInterface $subject, $result, array $sourceItems): void
    {
        $updatedSkus = [];
        foreach ($sourceItems as $sourceItem) {
            if ($sourceItem->hasDataChanges()) {
                $updatedSkus[] = $sourceItem->getSku();
            }
        }
        $this->scheduleProductUpdate->execute($updatedSkus);
    }
}
