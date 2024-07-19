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

declare(strict_types=1);
namespace Magento\SalesOrdersDataExporter\Plugin;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Uuid\UuidSaveException;
use Magento\Framework\Model\AbstractModel as CreditMemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as OrderResource;
use Magento\DataExporter\Uuid\UuidManager;

/**
 * Assign UUID to entity if it was not assigned before. Return assigned UUID or thrown Exception on failure
 */
class AssignUuidForCreditMemo
{
    private const TYPE = 'credit_memo';

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
     * @param Creditmemo $entity
     * @return OrderResource
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(OrderResource $subject, OrderResource $result, Creditmemo $entity): OrderResource
    {
        try {
            $entityId = (int) $entity->getId();
            $this->uidManager->assign($entityId, self::TYPE);
        } catch (UuidSaveException) {
            // do nothing, error logged in assign method
        } catch (\Throwable $e) {
            $this->logger->error('Cannot assign UUID to ' . self::TYPE . ' ' . $entity->getId(), ['exception' => $e]);
        }

        return $result;
    }
}
