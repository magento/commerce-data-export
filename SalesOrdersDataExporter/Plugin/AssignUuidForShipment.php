<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);
namespace Magento\SalesOrdersDataExporter\Plugin;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Uuid\UuidSaveException;
use Magento\Framework\Model\AbstractModel as Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment as OrderResource;
use Magento\DataExporter\Uuid\UuidManager;

/**
 * Assign UUID to entity if it was not assigned before. Return assigned UUID or thrown Exception on failure
 */
class AssignUuidForShipment
{
    private const TYPE = 'order_shipment';

    private UuidManager $uidManager;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param UuidManager $uidManager
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        UuidManager $uidManager,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->uidManager = $uidManager;
        $this->logger = $logger;
    }

    /**
     * @param OrderResource $subject
     * @param OrderResource $result
     * @param Shipment $entity
     * @return OrderResource
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(OrderResource $subject, OrderResource $result, Shipment $entity): OrderResource
    {
        $entityId = (int) $entity->getId();
        try {
            $this->uidManager->assign($entityId, self::TYPE);
        } catch (UuidSaveException) {
            // do nothing, error logged in assign method
        } catch (\Throwable $e) {
            $this->logger->error('Cannot assign UUID to ' . self::TYPE . ' ' . $entityId, ['exception' => $e]);
        }

        return $result;
    }
}
