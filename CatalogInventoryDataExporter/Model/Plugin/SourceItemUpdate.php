<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
